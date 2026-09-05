<?php

namespace Tests\Feature;

use App\Enums\SettingKey;
use App\Models\Lesson;
use App\Models\LessonQuiz;
use App\Models\LessonQuizQuestion;
use App\Models\Module;
use App\Models\Setting;
use App\Models\TeacherModuleQuizAttempt;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class TeacherModuleQuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_starting_an_attempt_records_the_questions_drawn(): void
    {
        [$teacher, $module] = $this->readyModule();

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.start', [$module->course, $module]))
            ->assertRedirect();

        $attempt = TeacherModuleQuizAttempt::sole();

        $this->assertCount(4, $attempt->question_ids);
        $this->assertNull($attempt->submitted_at);
    }

    public function test_restarting_resumes_the_open_attempt_rather_than_redrawing(): void
    {
        [$teacher, $module] = $this->readyModule();

        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));
        $first = TeacherModuleQuizAttempt::sole()->question_ids;

        // A refresh must not reroll the draw — otherwise a teacher could
        // keep restarting until they liked the questions.
        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));

        $this->assertSame(1, TeacherModuleQuizAttempt::count());
        $this->assertSame($first, TeacherModuleQuizAttempt::sole()->question_ids);
    }

    public function test_passing_certifies_the_teacher_for_the_module(): void
    {
        [$teacher, $module] = $this->readyModule();

        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.submit', [$module->course, $module]), [
                'answers' => $this->correctAnswers(),
            ])
            ->assertSessionHas('moduleQuizResult', fn (array $r) => $r['passed'] === true && $r['score'] === 100);

        $this->assertTrue($teacher->teacherProfile->fresh()->isCertifiedForModule($module));
    }

    public function test_a_score_below_the_pass_mark_does_not_certify(): void
    {
        [$teacher, $module] = $this->readyModule();

        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.submit', [$module->course, $module]), [
                'answers' => $this->wrongAnswers(),
            ])
            ->assertSessionHas('moduleQuizResult', fn (array $r) => $r['passed'] === false && $r['score'] === 0);

        $this->assertFalse($teacher->teacherProfile->fresh()->isCertifiedForModule($module));
    }

    public function test_grading_scores_against_the_drawn_questions_only(): void
    {
        [$teacher, $module] = $this->readyModule();

        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));

        $drawn = TeacherModuleQuizAttempt::sole()->question_ids;

        // Answer only half the drawn questions correctly.
        $answers = collect($drawn)
            ->mapWithKeys(fn ($id, $i) => [$id => $i < count($drawn) / 2 ? 'Correct' : 'Wrong'])
            ->all();

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.submit', [$module->course, $module]), ['answers' => $answers])
            ->assertSessionHas('moduleQuizResult', fn (array $r) => $r['score'] === 50 && $r['total'] === count($drawn));
    }

    public function test_a_teacher_is_blocked_after_using_every_attempt(): void
    {
        [$teacher, $module] = $this->readyModule();

        TeacherModuleQuizAttempt::factory()
            ->count(3)
            ->for($teacher->teacherProfile)
            ->for($module)
            ->create();

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.start', [$module->course, $module]))
            ->assertForbidden();
    }

    public function test_voided_attempts_do_not_count_toward_the_cap(): void
    {
        [$teacher, $module] = $this->readyModule();

        TeacherModuleQuizAttempt::factory()
            ->count(3)
            ->for($teacher->teacherProfile)
            ->for($module)
            ->voided()
            ->create();

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.start', [$module->course, $module]))
            ->assertRedirect();

        $this->assertSame(1, $teacher->teacherProfile->attemptsUsedFor($module));
    }

    public function test_an_already_certified_teacher_cannot_retake_it(): void
    {
        [$teacher, $module] = $this->readyModule();

        $teacher->teacherProfile->moduleCompletions()->create([
            'module_id' => $module->id,
            'completed_at' => now(),
            'quiz_passed' => true,
        ]);

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.start', [$module->course, $module]))
            ->assertForbidden();
    }

    public function test_the_attempt_cap_follows_the_admin_setting(): void
    {
        Setting::set(SettingKey::QuizMaxAttempts, 1);

        [$teacher, $module] = $this->readyModule();

        TeacherModuleQuizAttempt::factory()
            ->for($teacher->teacherProfile)
            ->for($module)
            ->create();

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.start', [$module->course, $module]))
            ->assertForbidden();
    }

    public function test_the_lms_page_only_exposes_questions_while_an_attempt_is_open(): void
    {
        [$teacher, $module] = $this->readyModule();

        $this->actingAs($teacher)
            ->get(route('lms.show', $module->course))
            ->assertInertia(fn ($page) => $page
                ->where('course.modules.0.quiz.can_attempt', true)
                ->where('course.modules.0.quiz.questions', [])
            );

        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));

        $this->actingAs($teacher)
            ->get(route('lms.show', $module->course))
            ->assertInertia(fn ($page) => $page->has('course.modules.0.quiz.questions', 4));
    }

    public function test_an_open_final_attempt_can_still_be_resumed_and_submitted(): void
    {
        [$teacher, $module] = $this->readyModule();

        // Burn attempts 1 and 2, then START the third without submitting.
        // The open attempt already counts toward the cap, so the cap check
        // must not lock the teacher out of finishing it.
        TeacherModuleQuizAttempt::factory()->count(2)
            ->for($teacher->teacherProfile)->for($module)->create();

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.start', [$module->course, $module]))
            ->assertRedirect();

        $this->assertSame(3, TeacherModuleQuizAttempt::count());
        $this->assertNull(TeacherModuleQuizAttempt::latest('id')->first()->submitted_at);

        // Teacher navigates away and comes back to finish it.
        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.start', [$module->course, $module]))
            ->assertRedirect();

        // The page must still offer the open attempt rather than the
        // out-of-attempts message.
        $this->actingAs($teacher)
            ->get(route('lms.show', $module->course))
            ->assertInertia(fn ($page) => $page
                ->where('course.modules.0.quiz.attempts_remaining', 0)
                ->where('course.modules.0.quiz.has_open_attempt', true)
                ->has('course.modules.0.quiz.questions', 4)
            );

        // And it must still be gradeable.
        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.submit', [$module->course, $module]), [
                'answers' => $this->correctAnswers(),
            ])
            ->assertSessionHas('moduleQuizResult', fn (array $r) => $r['passed'] === true);

        $this->assertTrue($teacher->teacherProfile->fresh()->isCertifiedForModule($module));
    }

    public function test_an_exhausted_teacher_with_no_open_attempt_still_cannot_start_one(): void
    {
        [$teacher, $module] = $this->readyModule();

        // All three submitted — nothing open to resume, so the cap applies.
        TeacherModuleQuizAttempt::factory()->count(3)
            ->for($teacher->teacherProfile)->for($module)->create();

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.start', [$module->course, $module]))
            ->assertForbidden();
    }

    public function test_adding_a_lesson_redraws_a_stale_open_attempt(): void
    {
        [$teacher, $module] = $this->readyModule();

        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));
        $before = TeacherModuleQuizAttempt::sole();

        // An admin adds a third lesson with its own published bank.
        $lesson = Lesson::factory()->for($module)->create();
        $quiz = LessonQuiz::factory()->published()->for($lesson)->create();
        foreach (range(1, 5) as $index) {
            $quiz->questions()->create([
                'question_text' => "New Q{$index}",
                'type' => 'multiple_choice',
                'options' => ['Correct', 'Wrong'],
                'correct_answer' => 'Correct',
                'sort_order' => $index,
            ]);
        }
        $teacher->teacherProfile->lessonCompletions()->create([
            'lesson_id' => $lesson->id,
            'completed_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.start', [$module->course, $module]))
            ->assertRedirect();

        $after = TeacherModuleQuizAttempt::sole();

        // Redrawn in place — not a second attempt, so the content change
        // doesn't cost the teacher one of their three.
        $this->assertSame($before->id, $after->id);
        $this->assertNotSame($before->bank_fingerprint, $after->bank_fingerprint);
        $this->assertSame(1, $teacher->teacherProfile->attemptsUsedFor($module));
    }

    public function test_removing_a_lesson_redraws_a_stale_open_attempt(): void
    {
        [$teacher, $module] = $this->readyModule();

        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));
        $before = TeacherModuleQuizAttempt::sole();

        $module->lessons()->first()->delete();

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.start', [$module->course, $module]))
            ->assertRedirect();

        $after = TeacherModuleQuizAttempt::sole()->fresh();

        $this->assertNotSame($before->bank_fingerprint, $after->bank_fingerprint);
        // Every drawn question must come from what still exists.
        $this->assertCount(4, $after->questions());
    }

    public function test_an_unchanged_module_still_resumes_rather_than_redrawing(): void
    {
        [$teacher, $module] = $this->readyModule();

        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));
        $before = TeacherModuleQuizAttempt::sole()->question_ids;

        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));

        $this->assertSame($before, TeacherModuleQuizAttempt::sole()->question_ids);
    }

    public function test_editing_a_question_in_place_does_not_redraw(): void
    {
        [$teacher, $module] = $this->readyModule();

        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));
        $before = TeacherModuleQuizAttempt::sole()->question_ids;

        // Rewording keeps the same question id, so the bank composition is
        // unchanged and the in-progress attempt survives.
        LessonQuizQuestion::find($before[0])->update(['question_text' => 'Reworded']);

        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));

        $this->assertSame($before, TeacherModuleQuizAttempt::sole()->question_ids);
    }

    /**
     * A teacher plus a module whose two lessons each hold a bank, with all
     * of the module's lessons already marked complete. Question count is
     * pinned to 4 so draws are predictable.
     *
     * @return array{0: User, 1: Module}
     */
    private function readyModule(): array
    {
        Setting::set(SettingKey::ModuleQuizQuestionCount, 4);

        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();

        foreach (range(1, 2) as $ignored) {
            $lesson = Lesson::factory()->for($module)->create();
            $quiz = LessonQuiz::factory()->published()->for($lesson)->create();

            foreach (range(1, 5) as $index) {
                $quiz->questions()->create([
                    'question_text' => "Q{$index}",
                    'type' => 'multiple_choice',
                    'options' => ['Correct', 'Wrong'],
                    'correct_answer' => 'Correct',
                    'sort_order' => $index,
                ]);
            }

            $teacher->teacherProfile->lessonCompletions()->create([
                'lesson_id' => $lesson->id,
                'completed_at' => now(),
            ]);
        }

        return [$teacher, $module];
    }

    /**
     * @return array<int, string>
     */
    private function correctAnswers(): array
    {
        return $this->answersFromOpenAttempt('Correct');
    }

    /**
     * @return array<int, string>
     */
    private function wrongAnswers(): array
    {
        return $this->answersFromOpenAttempt('Wrong');
    }

    /**
     * @return array<int, string>
     */
    private function answersFromOpenAttempt(string $answer): array
    {
        $open = TeacherModuleQuizAttempt::whereNull('submitted_at')->sole();

        return (new Collection($open->question_ids))
            ->mapWithKeys(fn (int $id) => [$id => $answer])
            ->all();
    }
}
