<?php

namespace Tests\Feature;

use App\Enums\LessonQuizStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonQuiz;
use App\Models\LessonSlideshow;
use App\Models\Module;
use App\Services\QuizGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class LessonQuizGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.openai.key' => 'test-key']);
    }

    public function test_it_generates_draft_ai_flagged_questions_from_slide_text(): void
    {
        $this->fakeOpenAiResponse([
            $this->question('What is a variable?', 'A named storage location'),
            $this->question('What is a loop?', 'Repeats a block of code'),
        ]);

        $course = Course::factory()->create(['grade_band' => 'Grade 5 - 6']);
        $module = Module::factory()->for($course)->create();
        $lesson = Lesson::factory()->for($module)->create();
        LessonSlideshow::factory()->for($lesson)->create([
            'slides_text' => "Slide 1: Variables\n\n---\n\nSlide 2: Loops",
        ]);

        $quiz = app(QuizGenerationService::class)->generate($lesson, 2);

        $this->assertSame(LessonQuizStatus::Draft, $quiz->status);
        $this->assertNotNull($quiz->generated_at);
        $this->assertSame(hash('sha256', "Slide 1: Variables\n\n---\n\nSlide 2: Loops"), $quiz->source_text_hash);
        $this->assertCount(2, $quiz->questions);
        $this->assertTrue($quiz->questions->every(fn ($question) => $question->is_ai_generated === true));
        $this->assertSame('What is a variable?', $quiz->questions->first()->question_text);
    }

    public function test_it_falls_back_to_reading_content_when_slides_text_is_empty(): void
    {
        $this->fakeOpenAiResponse([$this->question('What is Python?', 'A programming language')]);

        $lesson = Lesson::factory()->create(['reading_content' => 'Python is a programming language.']);
        LessonSlideshow::factory()->for($lesson)->create(['slides_text' => null]);

        $quiz = app(QuizGenerationService::class)->generate($lesson, 1);

        $this->assertSame(
            hash('sha256', 'Python is a programming language.'),
            $quiz->source_text_hash,
        );
    }

    public function test_it_throws_when_there_is_no_slide_text_or_reading_content(): void
    {
        $lesson = Lesson::factory()->create(['reading_content' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot generate a quiz');

        app(QuizGenerationService::class)->generate($lesson, 1);
    }

    public function test_it_fails_loudly_instead_of_saving_malformed_questions(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['questions' => [['question_text' => 'Missing options']]])]]],
            ]),
        ]);

        $lesson = Lesson::factory()->create();
        LessonSlideshow::factory()->for($lesson)->create(['slides_text' => 'Some slide content.']);

        try {
            app(QuizGenerationService::class)->generate($lesson, 1);
            $this->fail('Expected an exception for malformed OpenAI output.');
        } catch (\InvalidArgumentException) {
            // expected
        }

        $this->assertSame(0, LessonQuiz::count());
    }

    public function test_regenerating_replaces_existing_questions(): void
    {
        $lesson = Lesson::factory()->create();
        LessonSlideshow::factory()->for($lesson)->create(['slides_text' => 'Some slide content.']);

        $quiz = LessonQuiz::factory()->for($lesson)->create();
        $quiz->questions()->create([
            'question_text' => 'Old question',
            'type' => 'multiple_choice',
            'options' => ['A', 'B', 'C', 'D'],
            'correct_answer' => 'A',
            'is_ai_generated' => true,
            'sort_order' => 0,
        ]);

        $this->fakeOpenAiResponse([$this->question('New question?', 'New answer')]);

        $regenerated = app(QuizGenerationService::class)->generate($lesson, 1);

        $this->assertCount(1, $regenerated->questions);
        $this->assertSame('New question?', $regenerated->questions->first()->question_text);
        $this->assertDatabaseMissing('lesson_quiz_questions', ['question_text' => 'Old question']);
    }

    /**
     * @return array{question_text: string, options: list<string>, correct_answer: string, explanation: string}
     */
    private function question(string $text, string $correctAnswer): array
    {
        return [
            'question_text' => $text,
            'options' => [$correctAnswer, 'Wrong 1', 'Wrong 2', 'Wrong 3'],
            'correct_answer' => $correctAnswer,
            'explanation' => 'Because that is correct.',
        ];
    }

    /**
     * @param  list<array{question_text: string, options: list<string>, correct_answer: string, explanation: string}>  $questions
     */
    private function fakeOpenAiResponse(array $questions): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode(['questions' => $questions])]],
                ],
            ]),
        ]);
    }
}
