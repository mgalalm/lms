<?php

namespace App\Jobs;

use App\Models\ClassSession;
use App\Services\Zoom\ZoomClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class DownloadZoomRecordingJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public ClassSession $classSession, public string $downloadUrl)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ZoomClient $zoomClient): void
    {
        $contents = $zoomClient->downloadRecording($this->downloadUrl);

        $path = "recordings/{$this->classSession->id}.mp4";

        Storage::disk('local')->put($path, $contents);

        $this->classSession->update(['recap_path' => $path]);
    }
}
