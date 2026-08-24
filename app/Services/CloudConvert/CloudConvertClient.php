<?php

namespace App\Services\CloudConvert;

use CloudConvert\CloudConvert;
use CloudConvert\Models\Job;
use CloudConvert\Models\Task;
use RuntimeException;

/**
 * Thin wrapper around CloudConvert's official PHP SDK, used to convert an
 * admin-uploaded PPTX slideshow to PDF — not self-hosted (e.g. LibreOffice)
 * to avoid resource strain on the app server.
 */
class CloudConvertClient
{
    private CloudConvert $client;

    public function __construct()
    {
        $this->client = new CloudConvert([
            'api_key' => (string) config('services.cloudconvert.api_key'),
            'sandbox' => (bool) config('services.cloudconvert.sandbox'),
        ]);
    }

    /**
     * Upload a local file to CloudConvert, convert it to PDF, and return
     * the resulting PDF as a stream resource. Blocks until the job
     * finishes — callers should run this from a queued job, not a request.
     *
     * @return resource
     */
    public function convertToPdf(string $localPath, string $fileName)
    {
        $job = (new Job)
            ->addTask(new Task('import/upload', 'upload-slideshow'))
            ->addTask(
                (new Task('convert', 'convert-to-pdf'))
                    ->set('input', 'upload-slideshow')
                    ->set('output_format', 'pdf'),
            )
            ->addTask(
                (new Task('export/url', 'export-pdf'))
                    ->set('input', 'convert-to-pdf'),
            );

        $job = $this->client->jobs()->create($job);

        $uploadTask = $job->getTasks()->whereName('upload-slideshow')[0];

        $handle = fopen($localPath, 'r');

        try {
            $this->client->tasks()->upload($uploadTask, $handle, $fileName);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        $job = $this->client->jobs()->wait($job);

        if ($job->getStatus() === Job::STATUS_ERROR) {
            throw new RuntimeException("CloudConvert job failed converting {$fileName} to PDF.");
        }

        $exportedFiles = $job->getExportUrls();

        if (empty($exportedFiles)) {
            throw new RuntimeException("CloudConvert job produced no output file for {$fileName}.");
        }

        return $this->client->getHttpTransport()->download($exportedFiles[0]->url)->detach();
    }
}
