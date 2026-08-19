<?php

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Shapes a Course (with modules.lessons and quiz.questions eager loaded)
 * into the LMS course-detail page for the teacher dashboard, folding in
 * the viewing teacher's per-lesson progress and quiz eligibility.
 */
class LmsCourseDetailResource extends JsonResource
{
    /**
     * @param  Collection<int, int>  $completedLessonIds
     */
    public function __construct(
        Course $resource,
        private readonly Collection $completedLessonIds,
        private readonly bool $isCertified,
        private readonly bool $canTakeQuiz,
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalLessons = $this->modules->sum(fn ($module) => $module->lessons->count());

        return [
            'id' => $this->id,
            'title' => $this->title,
            'grade_band' => $this->grade_band,
            'thumbnail_url' => $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : null,
            'progress_percentage' => $totalLessons > 0
                ? (int) round($this->completedLessonIds->count() / $totalLessons * 100)
                : 0,
            'is_certified' => $this->isCertified,
            'can_take_quiz' => $this->canTakeQuiz,
            'modules' => $this->modules->map(fn ($module) => [
                'id' => $module->id,
                'title' => $module->title,
                'lessons' => $module->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'reading_content' => $lesson->reading_content,
                    'completed' => $this->completedLessonIds->contains($lesson->id),
                    // Teachers can preview a video at any stage (not just
                    // published) — see LessonVideoPolicy. bunny_video_id
                    // is required for playback, so a video still uploading
                    // (not yet pushed to Bunny) doesn't count as watchable.
                    'has_video' => $lesson->video !== null && $lesson->video->bunny_video_id !== null,
                ]),
            ]),
            'quiz' => $this->whenLoaded('quiz', fn () => $this->quiz ? [
                'passing_score' => $this->quiz->passing_score,
                'questions' => $this->canTakeQuiz
                    ? $this->quiz->questions->map(fn ($question) => [
                        'id' => $question->id,
                        'question' => $question->question,
                        'options' => $question->options,
                    ])
                    : [],
            ] : null),
        ];
    }
}
