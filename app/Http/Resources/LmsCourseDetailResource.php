<?php

namespace App\Http\Resources;

use App\Enums\SettingKey;
use App\Models\Course;
use App\Models\Module;
use App\Models\Setting;
use App\Models\TeacherProfile;
use App\Services\ModuleQuizAssembler;
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
            'modules' => $this->modules->map(fn ($module) => [
                'id' => $module->id,
                'title' => $module->title,
                'quiz' => $this->moduleQuizPayload($module),
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
                ]),
            ]),
        ];
    }

    /**
     * The viewing teacher's state for one module's certification quiz.
     *
     * Questions come from the teacher's open attempt, not from the module
     * directly: each attempt draws its own balanced sample (see
     * ModuleQuizAssembler), so the page must show exactly what was drawn
     * for them and nothing more.
     *
     * @return array<string, mixed>
     */
    private function moduleQuizPayload(Module $module): array
    {
        $assembler = app(ModuleQuizAssembler::class);

        $openAttempt = $this->teacherProfile->moduleQuizAttempts()
            ->counting()
            ->where('module_id', $module->id)
            ->whereNull('submitted_at')
            ->first();

        $isActive = $openAttempt !== null
            && session('moduleQuizStarted') === $module->id;

        return [
            'is_ready' => $assembler->isReady($module),
            'bank_size' => $assembler->bankSize($module),
            'certified' => $this->teacherProfile->isCertifiedForModule($module),
            'lessons_complete' => $this->teacherProfile->hasCompletedAllLessonsForModule($module),
            'can_attempt' => $this->teacherProfile->canAttemptModuleQuiz($module),
            // Only true for the render immediately after "Start Quiz".
            // Coming back to the module later drops the flash, so the
            // questions disappear and only the Start button remains — the
            // teacher gets a fresh draw rather than the stale one.
            //
            // Kept true even at zero attempts remaining: the open attempt
            // is itself one of the counted ones, so the page must let them
            // finish it rather than showing the out-of-attempts message.
            'is_active' => $isActive,
            'attempts_remaining' => $this->teacherProfile->attemptsRemainingFor($module),
            'passing_score' => (int) Setting::get(SettingKey::QuizPassingScore),
            'questions' => $isActive
                ? $openAttempt->questions()->map(fn ($question) => [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'options' => $question->options,
                ])->values()
                : [],
        ];
    }
}
