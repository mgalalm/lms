<?php

namespace Tests\Feature;

use App\Enums\SettingKey;
use App\Models\Lesson;
use App\Models\LessonQuiz;
use App\Models\LessonQuizQuestion;
use App\Models\Module;
use App\Models\Setting;
use App\Models\TeacherLessonQuizAttempt;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeacherLessonQuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_passing_attempt_is_recorded_and_unlocks_completion(): void
    {
        [$teacher, $lesson, $quiz, $questions] = $this->publishedQuizScenario();

        $this->actingAs($teacher)
            ->post(route('lms.lessons.quiz.submit', [$lesson->module->course, $lesson]), [
                'answers' => [
                    $questions[0]->id => 'A',
                    $questions[1]->id => 'A',
                ],
            ])
            ->assertSessionHas('teacherLessonQuizResult', fn (array $result) => $result['passed'] === true && $result['score'] === 100);

        $this->assertDatabaseHas('teacher_lesson_quiz_attempts', [
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'lesson_quiz_id' => $quiz->id,
            'score' => 100,
            'passed' => true,
        ]);

        $this->assertTrue($teacher->teacherProfile->fresh()->canCompleteLesson($lesson->fresh()));
    }

    public function test_a_score_below_the_configured_pass_mark_fails(): void
    {
        [$teacher, $lesson, , $questions] = $this->publishedQuizScenario();

        // One of two correct = 50%, under the default 80% pass mark.
        $this->actingAs($teacher)
            ->post(route('lms.lessons.quiz.submit', [$lesson->module->course, $lesson]), [
                'answers' => [
                    $questions[0]->id => 'A',
                    $questions[1]->id => 'B',
                ],
            ])
            ->assertSessionHas('teacherLessonQuizResult', fn (array $result) => $result['passed'] === false && $result['score'] === 50);

        $this->assertFalse($teacher->teacherProfile->fresh()->canCompleteLesson($lesson->fresh()));
    }

    public function test_the_pass_mark_follows_the_admin_setting(): void
    {
        Setting::set(SettingKey::QuizPassingScore, 50);

        [$teacher, $lesson, , $questions] = $this->publishedQuizScenario();

        $this->actingAs($teacher)
            ->post(route('lms.lessons.quiz.submit', [$lesson->module->course, $lesson]), [
                'answers' => [
                    $questions[0]->id => 'A',
                    $questions[1]->id => 'B',
                ],
            ])
            ->assertSessionHas('teacherLessonQuizResult', fn (array $result) => $result['passed'] === true);
    }

    public function test_a_teacher_is_blocked_after_using_every_attempt(): void
    {
        [$teacher, $lesson, $quiz, $questions] = $this->publishedQuizScenario();

        // Default cap is 3 attempts in total.
        TeacherLessonQuizAttempt::factory()
            ->count(3)
            ->for($teacher->teacherProfile)
            ->for($quiz, 'quiz')
            ->create();

        $this->actingAs($teacher)
            ->post(route('lms.lessons.quiz.submit', [$lesson->module->course, $lesson]), [
                'answers' => [$questions[0]->id => 'A', $questions[1]->id => 'A'],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('teacher_lesson_quiz_attempts', 3);
    }

    public function test_voided_attempts_do_not_count_toward_the_cap(): void
    {
        [$teacher, $lesson, $quiz, $questions] = $this->publishedQuizScenario();

        TeacherLessonQuizAttempt::factory()
            ->count(3)
            ->for($teacher->teacherProfile)
            ->for($quiz, 'quiz')
            ->voided()
            ->create();

        $this->actingAs($teacher)
            ->post(route('lms.lessons.quiz.submit', [$lesson->module->course, $lesson]), [
                'answers' => [$questions[0]->id => 'A', $questions[1]->id => 'A'],
            ])
            ->assertRedirect();

        $this->assertSame(1, $teacher->teacherProfile->attemptsUsedFor($quiz));
    }

    public function test_a_teacher_who_already_passed_cannot_burn_more_attempts(): void
    {
        [$teacher, $lesson, $quiz, $questions] = $this->publishedQuizScenario();

        TeacherLessonQuizAttempt::factory()
            ->for($teacher->teacherProfile)
            ->for($quiz, 'quiz')
            ->passed()
            ->create();

        $this->actingAs($teacher)
            ->post(route('lms.lessons.quiz.submit', [$lesson->module->course, $lesson]), [
                'answers' => [$questions[0]->id => 'A', $questions[1]->id => 'A'],
            ])
            ->assertForbidden();
    }

    public function test_a_draft_quiz_cannot_be_attempted_by_a_teacher(): void
    {
        [$teacher, $lesson, $quiz, $questions] = $this->publishedQuizScenario();

        $quiz->update(['status' => 'draft']);

        $this->actingAs($teacher)
            ->post(route('lms.lessons.quiz.submit', [$lesson->module->course, $lesson]), [
                'answers' => [$questions[0]->id => 'A', $questions[1]->id => 'A'],
            ])
            ->assertForbidden();
    }

    public function test_the_lms_page_withholds_questions_once_attempts_are_exhausted(): void
    {
        [$teacher, $lesson, $quiz] = $this->publishedQuizScenario();

        TeacherLessonQuizAttempt::factory()
            ->count(3)
            ->for($teacher->teacherProfile)
            ->for($quiz, 'quiz')
            ->create();

        $this->actingAs($teacher)
            ->get(route('lms.show', $lesson->module->course))
            ->assertInertia(fn (Assert $page) => $page
                ->where('course.modules.0.lessons.0.quiz.can_attempt', false)
                ->where('course.modules.0.lessons.0.quiz.attempts_remaining', 0)
                ->where('course.modules.0.lessons.0.quiz.questions', [])
            );
    }

    public function test_the_lms_page_reports_no_quiz_when_none_is_published(): void
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lesson = Lesson::factory()->for($module)->create();

        $this->actingAs($teacher)
            ->get(route('lms.show', $module->course))
            ->assertInertia(fn (Assert $page) => $page->where('course.modules.0.lessons.0.quiz', null));
    }

    /**
     * A teacher plus a published two-question quiz whose correct answer is
     * 'A' for both.
     *
     * @return array{User, Lesson, LessonQuiz, list<LessonQuizQuestion>}
     */
    private function publishedQuizScenario(): array
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lesson = Lesson::factory()->for($module)->create();
        $quiz = LessonQuiz::factory()->published()->for($lesson)->create();

        $questions = collect(range(0, 1))->map(fn (int $index) => $quiz->questions()->create([
            'question_text' => "Q{$index}",
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'sort_order' => $index,
        ]))->all();

        return [$teacher, $lesson, $quiz, $questions];
    }
}
