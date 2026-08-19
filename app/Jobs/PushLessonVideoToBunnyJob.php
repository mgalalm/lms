<?php

namespace App\Jobs;

use App\Enums\LessonVideoStatus;
use App\Models\LessonVideo;
use App\Services\Bunny\BunnyStreamClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class PushLessonVideoToBunnyJob implements ShouldQueue
{
    use Queueable;

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

        $videoId = $bunny->createVideo($this->lessonVideo->lesson->title);

        $bunny->uploadVideo($videoId, $disk->get($this->uploadPath));

        $this->lessonVideo->update([
            'bunny_video_id' => $videoId,
            'status' => LessonVideoStatus::Processing,
        ]);

        $disk->delete($this->uploadPath);
    }
}
