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
        $this->assertSame(app(QuizGenerationService::class)->sourceHash($lesson), $quiz->source_text_hash);
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
            app(QuizGenerationService::class)->sourceHash($lesson, 'Python is a programming language.'),
            $quiz->source_text_hash,
        );
    }

    public function test_the_hash_changes_when_the_lesson_is_retitled_so_the_staleness_warning_fires(): void
    {
        $lesson = Lesson::factory()->create(['title' => 'L1: Variables']);
        LessonSlideshow::factory()->for($lesson)->create(['slides_text' => 'Slide content.']);

        $before = app(QuizGenerationService::class)->sourceHash($lesson);

        $lesson->update(['title' => 'L1: Loops']);

        $this->assertNotSame($before, app(QuizGenerationService::class)->sourceHash($lesson->fresh()));
    }

    public function test_the_prompt_carries_the_lesson_titles_and_an_age_appropriate_guardrail(): void
    {
        $this->fakeOpenAiResponse([$this->question('What is a variable?', 'A named store')]);

        $course = Course::factory()->create(['grade_band' => 'Grade 5 - 6']);
        $module = Module::factory()->for($course)->create(['title' => 'M2: Chatbot Development']);
        $lesson = Lesson::factory()->for($module)->create(['title' => 'L11: Tkinter in Python']);
        LessonSlideshow::factory()->for($lesson)->create(['slides_text' => 'Variables store values.']);

        app(QuizGenerationService::class)->generate($lesson, 1);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/chat/completions')) {
                return false;
            }

            $prompt = $request->data()['messages'][1]['content'];

            return str_contains($prompt, 'L11: Tkinter in Python')
                && str_contains($prompt, 'M2: Chatbot Development')
                && str_contains($prompt, 'Grade 5 - 6')
                // Grade band translated to a concrete age range (grade + 5/6).
                && str_contains($prompt, '10-12 years old')
                && str_contains($prompt, 'school-appropriate for minors');
        });
    }

    public function test_an_unrecognised_grade_band_omits_the_age_range_rather_than_guessing(): void
    {
        $this->fakeOpenAiResponse([$this->question('Q?', 'A')]);

        $course = Course::factory()->create(['grade_band' => 'Foundation']);
        $module = Module::factory()->for($course)->create();
        $lesson = Lesson::factory()->for($module)->create();
        LessonSlideshow::factory()->for($lesson)->create(['slides_text' => 'Content.']);

        app(QuizGenerationService::class)->generate($lesson, 1);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/chat/completions')) {
                return false;
            }

            $prompt = $request->data()['messages'][1]['content'];

            return str_contains($prompt, 'students in Foundation.')
                && ! str_contains($prompt, 'years old');
        });
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

    public function test_it_discards_generated_questions_that_moderation_flags(): void
    {
        $this->fakeOpenAiResponse([$this->question('Something unsuitable?', 'An answer')], flagged: true);

        $lesson = Lesson::factory()->create();
        LessonSlideshow::factory()->for($lesson)->create(['slides_text' => 'Some slide content.']);

        try {
            app(QuizGenerationService::class)->generate($lesson, 1);
            $this->fail('Expected flagged content to fail the generation.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('flagged as unsuitable for students', $e->getMessage());
            $this->assertStringContainsString('violence', $e->getMessage());
        }

        $this->assertSame(0, LessonQuiz::count());
        $this->assertDatabaseCount('lesson_quiz_questions', 0);
    }

    public function test_it_fails_closed_when_the_moderation_check_itself_errors(): void
    {
        Http::fake([
            'api.openai.com/v1/moderations' => Http::response([], 500),
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['questions' => [$this->question('Q?', 'A')]])]]],
            ]),
        ]);

        $lesson = Lesson::factory()->create();
        LessonSlideshow::factory()->for($lesson)->create(['slides_text' => 'Some slide content.']);

        try {
            app(QuizGenerationService::class)->generate($lesson, 1);
            $this->fail('Expected a moderation outage to fail the generation.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Could not safety-check', $e->getMessage());
        }

        $this->assertSame(0, LessonQuiz::count());
    }

    public function test_moderation_sees_every_field_a_student_would_read(): void
    {
        $this->fakeOpenAiResponse([[
            'question_text' => 'QUESTION-MARKER?',
            'options' => ['OPTION-MARKER', 'B', 'C', 'D'],
            'correct_answer' => 'OPTION-MARKER',
            'explanation' => 'EXPLANATION-MARKER',
        ]]);

        $lesson = Lesson::factory()->create();
        LessonSlideshow::factory()->for($lesson)->create(['slides_text' => 'Some slide content.']);

        app(QuizGenerationService::class)->generate($lesson, 1);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/moderations')) {
                return false;
            }

            $input = $request->data()['input'];

            return str_contains($input, 'QUESTION-MARKER?')
                && str_contains($input, 'OPTION-MARKER')
                && str_contains($input, 'EXPLANATION-MARKER');
        });
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
     * Fakes both OpenAI endpoints this feature touches: chat completions for
     * generation, and moderations for the safety check that follows it.
     *
     * @param  list<array{question_text: string, options: list<string>, correct_answer: string, explanation: string}>  $questions
     */
    private function fakeOpenAiResponse(array $questions, bool $flagged = false): void
    {
        Http::fake([
            'api.openai.com/v1/moderations' => Http::response([
                'results' => [[
                    'flagged' => $flagged,
                    'categories' => ['violence' => $flagged, 'sexual' => false],
                ]],
            ]),
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode(['questions' => $questions])]],
                ],
            ]),
        ]);
    }
}
