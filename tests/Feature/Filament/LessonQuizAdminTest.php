<?php

namespace Tests\Feature\Filament;

use App\Enums\LessonQuizStatus;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\Lesson;
use App\Models\LessonQuiz;
use App\Models\LessonSlideshow;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class LessonQuizAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        config(['services.openai.key' => 'test-key']);
    }

    public function test_an_admin_can_generate_a_draft_quiz_from_slide_text(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode(['questions' => [
                        ['question_text' => 'What is a loop?', 'options' => ['Repeats code', 'A', 'B', 'C'], 'correct_answer' => 'Repeats code', 'explanation' => 'It repeats.'],
                    ]])],
                ]],
            ]),
        ]);

        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();
        LessonSlideshow::factory()->for($lesson)->create(['slides_text' => 'Loops repeat a block of code.']);

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->callAction('generateQuiz', ['question_count' => 1])
            ->assertNotified('Quiz generated');

        $quiz = $lesson->quiz()->firstOrFail();

        $this->assertSame(LessonQuizStatus::Draft, $quiz->status);
        $this->assertCount(1, $quiz->questions);
        $this->assertTrue($quiz->questions->first()->is_ai_generated);
    }

    public function test_the_generate_quiz_action_is_disabled_and_blocked_when_slides_text_is_empty(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create(['reading_content' => null]);

        $this->actingAs($admin);

        // The action is disabled() when there's no slide text, which
        // Filament enforces server-side too (InteractsWithActions checks
        // isDisabled() before running the action) — callAction() is a no-op
        // here rather than throwing, so nothing gets persisted.
        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->assertActionDisabled('generateQuiz')
            ->callAction('generateQuiz', ['question_count' => 5])
            ->assertNotNotified();

        $this->assertSame(0, LessonQuiz::count());
    }

    public function test_manual_edits_to_quiz_questions_survive_a_save(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();
        $quiz = LessonQuiz::factory()->for($lesson)->create();
        $quiz->questions()->create([
            'question_text' => 'AI question',
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'is_ai_generated' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->fillForm([
                'quiz_questions' => [
                    [
                        'question_text' => 'Rewritten by admin',
                        'type' => 'multiple_choice',
                        'options' => ['X', 'Y', 'Z', 'W'],
                        'correct_answer' => 'Y',
                        'explanation' => 'Because I said so.',
                        'is_ai_generated' => false,
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $quiz->refresh();

        $this->assertCount(1, $quiz->questions);
        $this->assertSame('Rewritten by admin', $quiz->questions->first()->question_text);
        $this->assertSame('Y', $quiz->questions->first()->correct_answer);
        $this->assertFalse($quiz->questions->first()->is_ai_generated);
    }

    public function test_publishing_a_quiz_flips_its_status(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();
        $quiz = LessonQuiz::factory()->for($lesson)->create(['status' => LessonQuizStatus::Draft]);
        $quiz->questions()->create([
            'question_text' => 'Q',
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'sort_order' => 0,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->callAction('publishQuiz')
            ->assertNotified('Quiz published');

        $this->assertSame(LessonQuizStatus::Published, $quiz->fresh()->status);
    }

    public function test_the_generate_action_is_disabled_once_the_quiz_hits_the_generation_limit(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();
        LessonSlideshow::factory()->for($lesson)->create(['slides_text' => 'Some content.']);
        LessonQuiz::factory()->for($lesson)->generationLimitReached()->create();

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->assertActionDisabled('generateQuiz');
    }

    public function test_the_generate_action_stays_enabled_while_generations_remain(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();
        LessonSlideshow::factory()->for($lesson)->create(['slides_text' => 'Some content.']);
        LessonQuiz::factory()->for($lesson)->create(['generation_count' => 1]);

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->assertActionEnabled('generateQuiz');
    }

    public function test_regenerating_an_existing_quiz_warns_before_replacing_it(): void
    {
        $admin = User::factory()->admin()->create();
        $lesson = Lesson::factory()->create();
        LessonSlideshow::factory()->for($lesson)->create(['slides_text' => 'Some content.']);
        $quiz = LessonQuiz::factory()->for($lesson)->create();
        $quiz->questions()->create([
            'question_text' => 'Existing',
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'sort_order' => 0,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->mountAction('generateQuiz')
            ->assertMountedActionModalSee('replace ALL existing questions');
    }
}
