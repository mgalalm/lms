<?php

namespace App\Jobs;

use App\Models\LessonSlideshow;
use App\Services\CloudConvert\CloudConvertClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ConvertLessonSlideshowToPdfJob implements ShouldQueue
{
    use Queueable;

    /**
     * Generous ceiling for the CloudConvert round trip (upload + convert +
     * download) — Horizon's global default (config/horizon.php) is only
     * 60 seconds, which would kill this job mid-conversion for anything
     * but a tiny deck.
     */
    public int $timeout = 3600;

    /**
     * Don't auto-retry: a retry would start a whole new CloudConvert job
     * while the previous one may still be running. If this fails, re-
     * upload manually.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(public LessonSlideshow $lessonSlideshow, public string $uploadDisk, public string $uploadPath)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CloudConvertClient $cloudConvert): void
    {
        $disk = Storage::disk($this->uploadDisk);

        if (! $disk->exists($this->uploadPath)) {
            throw new RuntimeException("Lesson slideshow upload file missing before conversion: {$this->uploadPath}");
        }

        $pdfStream = $cloudConvert->convertToPdf($disk->path($this->uploadPath), basename($this->uploadPath));

        $destinationPath = "lesson-slideshows/{$this->lessonSlideshow->lesson_id}/".$this->lessonSlideshow->locale.'.pdf';

        try {
            Storage::disk('bunny')->put($destinationPath, $pdfStream);
        } finally {
            if (is_resource($pdfStream)) {
                fclose($pdfStream);
            }
        }

        $this->lessonSlideshow->update(['file_path' => $destinationPath]);

        $disk->delete($this->uploadPath);
    }
}
