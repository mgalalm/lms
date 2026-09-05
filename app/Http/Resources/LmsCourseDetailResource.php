<?php

namespace App\Http\Resources;

use App\Enums\LessonQuizStatus;
use App\Enums\SettingKey;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Setting;
use App\Models\TeacherProfile;
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
        private readonly TeacherProfile $teacherProfile,
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
                    'slideshow' => $lesson->slideshow?->google_slides_id
                        ? ['google_slides_id' => $lesson->slideshow->google_slides_id]
                        : null,
                    // Passing the lesson's quiz is what unlocks "Mark as
                    // complete" — see TeacherProfile::canCompleteLesson().
                    'quiz' => $this->lessonQuizPayload($lesson),
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

    /**
     * The viewing teacher's state for one lesson's quiz. Returns null when
     * no published quiz exists — the lesson then can't be completed at
     * all, and the page explains why rather than offering a dead button.
     *
     * Questions are withheld once the teacher has passed or run out of
     * attempts, so the payload never hands out a quiz they can't take.
     *
     * @return array<string, mixed>|null
     */
    private function lessonQuizPayload(Lesson $lesson): ?array
    {
        $quiz = $lesson->quiz;

        if (! $quiz || $quiz->status !== LessonQuizStatus::Published) {
            return null;
        }

        $canAttempt = $this->teacherProfile->canAttemptLessonQuiz($quiz);

        return [
            'id' => $quiz->id,
            'passed' => $this->teacherProfile->hasPassedLessonQuiz($quiz),
            'can_attempt' => $canAttempt,
            'attempts_remaining' => $this->teacherProfile->attemptsRemainingFor($quiz),
            'passing_score' => (int) Setting::get(SettingKey::QuizPassingScore),
            'questions' => $canAttempt
                ? $quiz->questions->map(fn ($question) => [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'options' => $question->options,
                ])->values()
                : [],
        ];
    }
}
