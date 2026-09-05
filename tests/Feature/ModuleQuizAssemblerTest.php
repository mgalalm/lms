<?php

namespace Tests\Feature;

use App\Enums\SettingKey;
use App\Models\Lesson;
use App\Models\LessonQuiz;
use App\Models\Module;
use App\Models\Setting;
use App\Services\ModuleQuizAssembler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleQuizAssemblerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_draws_the_configured_number_of_questions(): void
    {
        Setting::set(SettingKey::ModuleQuizQuestionCount, 6);

        $module = Module::factory()->create();
        $this->lessonWithBank($module, 10);
        $this->lessonWithBank($module, 10);

        $this->assertCount(6, app(ModuleQuizAssembler::class)->assemble($module));
    }

    public function test_it_spreads_questions_evenly_across_lessons(): void
    {
        Setting::set(SettingKey::ModuleQuizQuestionCount, 6);

        $module = Module::factory()->create();
        $a = $this->lessonWithBank($module, 20);
        $b = $this->lessonWithBank($module, 20);
        $c = $this->lessonWithBank($module, 20);

        $drawn = app(ModuleQuizAssembler::class)->assemble($module);

        $byLesson = $drawn->groupBy(fn ($question) => $question->quiz->lesson_id);

        // 6 across 3 equal banks = exactly 2 each, no lesson dominating.
        $this->assertSame(2, $byLesson[$a->id]->count());
        $this->assertSame(2, $byLesson[$b->id]->count());
        $this->assertSame(2, $byLesson[$c->id]->count());
    }

    public function test_a_small_bank_does_not_stop_the_draw_reaching_its_target(): void
    {
        Setting::set(SettingKey::ModuleQuizQuestionCount, 10);

        $module = Module::factory()->create();
        $small = $this->lessonWithBank($module, 1);
        $large = $this->lessonWithBank($module, 20);

        $drawn = app(ModuleQuizAssembler::class)->assemble($module);
        $byLesson = $drawn->groupBy(fn ($question) => $question->quiz->lesson_id);

        $this->assertCount(10, $drawn);
        // The small bank contributes everything it has, the rest comes
        // from the large one rather than the draw falling short.
        $this->assertSame(1, $byLesson[$small->id]->count());
        $this->assertSame(9, $byLesson[$large->id]->count());
    }

    public function test_it_returns_a_shorter_quiz_when_the_bank_is_too_small(): void
    {
        Setting::set(SettingKey::ModuleQuizQuestionCount, 10);

        $module = Module::factory()->create();
        $this->lessonWithBank($module, 3);

        $this->assertCount(3, app(ModuleQuizAssembler::class)->assemble($module));
    }

    public function test_it_skips_lessons_without_a_published_quiz(): void
    {
        Setting::set(SettingKey::ModuleQuizQuestionCount, 10);

        $module = Module::factory()->create();
        $published = $this->lessonWithBank($module, 4);

        // A draft quiz, and a lesson with no quiz at all — neither should
        // contribute, but neither should block the module either.
        $draftLesson = Lesson::factory()->for($module)->create();
        $draftQuiz = LessonQuiz::factory()->for($draftLesson)->create();
        $this->addQuestions($draftQuiz, 5);
        Lesson::factory()->for($module)->create();

        $drawn = app(ModuleQuizAssembler::class)->assemble($module);

        $this->assertCount(4, $drawn);
        $this->assertTrue($drawn->every(fn ($question) => $question->quiz->lesson_id === $published->id));
    }

    public function test_a_module_with_no_questions_is_not_ready(): void
    {
        $module = Module::factory()->create();
        Lesson::factory()->for($module)->create();

        $assembler = app(ModuleQuizAssembler::class);

        $this->assertFalse($assembler->isReady($module));
        $this->assertSame(0, $assembler->bankSize($module));
        $this->assertTrue($assembler->assemble($module)->isEmpty());
    }

    public function test_successive_draws_differ(): void
    {
        Setting::set(SettingKey::ModuleQuizQuestionCount, 5);

        $module = Module::factory()->create();
        $this->lessonWithBank($module, 40);

        $assembler = app(ModuleQuizAssembler::class);

        // Drawing from a 40-question bank, two identical 5-question draws
        // in the same order is vanishingly unlikely unless it isn't random.
        $first = $assembler->assemble($module)->pluck('id')->all();
        $second = $assembler->assemble($module)->pluck('id')->all();

        $this->assertNotSame($first, $second);
    }

    private function lessonWithBank(Module $module, int $questionCount): Lesson
    {
        $lesson = Lesson::factory()->for($module)->create();
        $quiz = LessonQuiz::factory()->published()->for($lesson)->create();

        $this->addQuestions($quiz, $questionCount);

        return $lesson;
    }

    private function addQuestions(LessonQuiz $quiz, int $count): void
    {
        foreach (range(1, $count) as $index) {
            $quiz->questions()->create([
                'question_text' => "Q{$index}",
                'type' => 'multiple_choice',
                'options' => ['A', 'B', 'C', 'D'],
                'correct_answer' => 'A',
                'sort_order' => $index,
            ]);
        }
    }
}
