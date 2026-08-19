<?php

namespace App\Jobs;

use App\Enums\LessonVideoStatus;
use App\Models\LessonVideo;
use App\Services\Bunny\BunnyStreamClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PushLessonVideoToBunnyJob implements ShouldQueue
{
    use Queueable;

    /**
     * Generous ceiling for pushing a large video file to Bunny over HTTP —
     * Horizon's global default (config/horizon.php) is only 60 seconds,
     * which kills this job mid-upload for anything but a tiny file. A
     * killed upload leaves Bunny with a truncated/corrupt video that never
     * finishes transcoding.
     */
    public int $timeout = 3600;

    /**
     * Don't auto-retry: a retry would call createVideo() again and orphan
     * the previous (possibly partially-uploaded) video on Bunny's side.
     * If this fails, re-upload manually via "Replace Video".
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(public LessonVideo $lessonVideo, public string $uploadDisk, public string $uploadPath)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(BunnyStreamClient $bunny): void
    {
        $disk = Storage::disk($this->uploadDisk);

        // Streamed, not Storage::get() — a 500MB file read into a single
        // PHP string risks exhausting the worker's memory_limit, and Guzzle
        // would buffer that same string again when sending it.
        $stream = $disk->readStream($this->uploadPath);

        if (! $stream) {
            throw new RuntimeException("Lesson video upload file missing before push to Bunny: {$this->uploadPath}");
        }

        $videoId = $bunny->createVideo($this->lessonVideo->lesson->title);

        try {
            $bunny->uploadVideo($videoId, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $this->lessonVideo->update([
            'bunny_video_id' => $videoId,
            'status' => LessonVideoStatus::Processing,
        ]);

        $disk->delete($this->uploadPath);
    }
}
