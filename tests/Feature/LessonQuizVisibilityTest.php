<?php

namespace Tests\Feature;

use App\Enums\LessonQuizStatus;
use App\Enums\SessionType;
use App\Models\ClassSession;
use App\Models\Lesson;
use App\Models\LessonQuiz;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LessonQuizVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_draft_quiz_is_not_exposed_on_the_student_dashboard(): void
    {
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create();
        LessonQuiz::factory()->for($lesson)->create(['status' => LessonQuizStatus::Draft]);

        $this->currentLessonFor($student, $lesson);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('currentLesson.quiz', null));
    }

    public function test_a_published_quiz_is_exposed_on_the_student_dashboard(): void
    {
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create();
        $quiz = LessonQuiz::factory()->published()->for($lesson)->create();
        $quiz->questions()->create([
            'question_text' => 'Q1',
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'sort_order' => 0,
        ]);

        $this->currentLessonFor($student, $lesson);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('currentLesson.quiz.id', $quiz->id)
                ->has('currentLesson.quiz.questions', 1)
                // Answers must never reach the client before submission.
                ->missing('currentLesson.quiz.questions.0.correct_answer')
            );
    }

    public function test_a_student_cannot_submit_an_attempt_against_a_draft_quiz(): void
    {
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create();
        $quiz = LessonQuiz::factory()->for($lesson)->create(['status' => LessonQuizStatus::Draft]);
        $question = $quiz->questions()->create([
            'question_text' => 'Q1',
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'sort_order' => 0,
        ]);

        $this->actingAs($student)
            ->post(route('lessons.quiz.submit', $lesson), ['answers' => [$question->id => 'A']])
            ->assertForbidden();
    }

    public function test_a_student_can_submit_and_be_scored_against_a_published_quiz(): void
    {
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create();
        $quiz = LessonQuiz::factory()->published()->for($lesson)->create();
        $correctQuestion = $quiz->questions()->create([
            'question_text' => 'Q1', 'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'], 'correct_answer' => 'A', 'sort_order' => 0,
        ]);
        $wrongQuestion = $quiz->questions()->create([
            'question_text' => 'Q2', 'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'], 'correct_answer' => 'B', 'sort_order' => 1,
        ]);

        $response = $this->actingAs($student)->post(route('lessons.quiz.submit', $lesson), [
            'answers' => [
                $correctQuestion->id => 'A',
                $wrongQuestion->id => 'C',
            ],
        ]);

        $response->assertSessionHas('lessonQuizResult', function (array $result) {
            return $result['score'] === 50 && $result['correct'] === 1 && $result['total'] === 2;
        });
    }

    public function test_a_teacher_cannot_submit_an_attempt(): void
    {
        $teacher = User::factory()->teacher()->create();
        $lesson = Lesson::factory()->create();
        LessonQuiz::factory()->published()->for($lesson)->create();

        $this->actingAs($teacher)
            ->post(route('lessons.quiz.submit', $lesson), ['answers' => []])
            ->assertForbidden(); // role:student middleware rejects before the controller runs
    }

    private function currentLessonFor(User $student, Lesson $lesson): void
    {
        ClassSession::factory()->completed()->create([
            'student_id' => $student->id,
            'lesson_id' => $lesson->id,
            'type' => SessionType::Physical,
        ]);
    }
}
