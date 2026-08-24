<?php

namespace App\Jobs;

use App\Models\LessonSlideshow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Pushes an already-PDF slideshow upload straight to Bunny — no
 * CloudConvert step. For a PPTX upload, see ConvertLessonSlideshowToPdfJob
 * instead. Queued (not done inline in the Filament action) so a large PDF
 * doesn't block the web request.
 */
class PushLessonSlideshowToBunnyJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

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
    public function handle(): void
    {
        $disk = Storage::disk($this->uploadDisk);

        $stream = $disk->readStream($this->uploadPath);

        if (! $stream) {
            throw new RuntimeException("Lesson slideshow upload file missing before push to Bunny: {$this->uploadPath}");
        }

        $destinationPath = "lesson-slideshows/{$this->lessonSlideshow->lesson_id}/{$this->lessonSlideshow->locale}.pdf";

        try {
            Storage::disk('bunny')->put($destinationPath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $this->lessonSlideshow->update(['file_path' => $destinationPath]);

        $disk->delete($this->uploadPath);
    }
}
