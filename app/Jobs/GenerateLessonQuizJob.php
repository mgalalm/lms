<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Services\QuizGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateLessonQuizJob implements ShouldQueue
{
    use Queueable;

    /**
     * Dispatched synchronously (Bus::dispatchNow) from the Filament "Generate
     * Quiz" action so the admin gets an immediate success/failure
     * Notification — same pattern as SyncNotionContentJob. Retrying
     * wouldn't help a validation/API failure, so don't auto-retry.
     */
    public int $tries = 1;

    public function __construct(public Lesson $lesson, public int $questionCount = 5)
    {
        //
    }

    public function handle(QuizGenerationService $quizGenerationService): void
    {
        $quizGenerationService->generate($this->lesson, $this->questionCount);
    }
}
