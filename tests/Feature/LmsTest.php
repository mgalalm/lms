<?php

namespace Tests\Feature;

use App\Enums\SessionCreditReason;
use App\Filament\Resources\ClassSessions\Pages\CreateClassSession;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonQuiz;
use App\Models\LessonVideo;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Tests\TestCase;

class LmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_teacher_can_view_their_course_library(): void
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->for(Course::factory()->state(['grade_band' => 'Grade 1-2']))->create();
        Lesson::factory()->for($module)->count(2)->create();

        $this->actingAs($teacher)
            ->get(route('lms.index'))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Lms/Index')
                ->where('filter', 'all')
                ->has('gradeBands', 1)
                ->where('gradeBands.0.grade_band', 'Grade 1-2')
                ->where('gradeBands.0.courses.0.lessons_count', 2)
                ->where('gradeBands.0.courses.0.status', 'not_started')
            );
    }

    public function test_students_cannot_view_the_lms(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('lms.index'))->assertForbidden();
    }

    public function test_guests_are_redirected_away_from_the_lms(): void
    {
        $this->get(route('lms.index'))->assertRedirect('/login');
    }

    public function test_teacher_can_view_a_course_detail_page(): void
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lesson = Lesson::factory()->for($module)->create();

        $this->actingAs($teacher)
            ->get(route('lms.show', $module->course))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Lms/Show')
                ->where('course.modules.0.lessons.0.id', $lesson->id)
                ->where('course.modules.0.lessons.0.completed', false)
                ->where('course.is_certified', false)
            );
    }

    public function test_course_detail_reports_whether_a_lesson_has_a_watchable_video(): void
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lessonWithVideo = Lesson::factory()->for($module)->create();
        $lessonStillUploading = Lesson::factory()->for($module)->create();

        LessonVideo::factory()->for($lessonWithVideo)->create(['bunny_video_id' => 'bunny-123']);
        LessonVideo::factory()->for($lessonStillUploading)->create(['bunny_video_id' => null]);

        $this->actingAs($teacher)
            ->get(route('lms.show', $module->course))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Lms/Show')
                ->where('course.modules.0.lessons.0.has_video', true)
                ->where('course.modules.0.lessons.1.has_video', false)
            );
    }

    public function test_teacher_can_mark_a_lesson_complete(): void
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lesson = Lesson::factory()->for($module)->create();

        $this->actingAs($teacher)
            ->post(route('lms.lessons.complete', [$module->course, $lesson]))
            ->assertRedirect();

        $this->assertDatabaseHas('teacher_lesson_completions', [
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_marking_a_lesson_complete_twice_does_not_duplicate_the_record(): void
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lesson = Lesson::factory()->for($module)->create();

        $this->actingAs($teacher)->post(route('lms.lessons.complete', [$module->course, $lesson]));
        $this->actingAs($teacher)->post(route('lms.lessons.complete', [$module->course, $lesson]));

        $this->assertDatabaseCount('teacher_lesson_completions', 1);
    }

    public function test_teacher_cannot_start_the_module_quiz_before_completing_its_lessons(): void
    {
        [$teacher, $module] = $this->moduleWithQuestionBank();

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.start', [$module->course, $module]))
            ->assertForbidden();
    }

    public function test_teacher_who_passes_the_module_quiz_becomes_certified_for_it(): void
    {
        [$teacher, $module, $questions] = $this->moduleWithQuestionBank();

        $this->completeLessons($teacher, $module);

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.start', [$module->course, $module]))
            ->assertRedirect();

        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => $q->correct_answer])->all();

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.submit', [$module->course, $module]), ['answers' => $answers])
            ->assertRedirect();

        $this->assertTrue($teacher->teacherProfile->fresh()->isCertifiedForModule($module));
    }

    public function test_teacher_who_fails_the_module_quiz_is_not_certified(): void
    {
        [$teacher, $module, $questions] = $this->moduleWithQuestionBank();

        $this->completeLessons($teacher, $module);

        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));

        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 'Wrong'])->all();

        $this->actingAs($teacher)
            ->post(route('lms.modules.quiz.submit', [$module->course, $module]), ['answers' => $answers])
            ->assertRedirect();

        $this->assertFalse($teacher->teacherProfile->fresh()->isCertifiedForModule($module));
    }

    public function test_passing_every_module_rolls_up_into_course_certification(): void
    {
        [$teacher, $module, $questions] = $this->moduleWithQuestionBank();

        $this->completeLessons($teacher, $module);
        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));

        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => $q->correct_answer])->all();
        $this->actingAs($teacher)->post(route('lms.modules.quiz.submit', [$module->course, $module]), ['answers' => $answers]);

        // The course has exactly one module, so passing it certifies the course.
        $this->assertDatabaseHas('teacher_course_completions', [
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'course_id' => $module->course_id,
            'quiz_passed' => true,
        ]);
    }

    public function test_completing_the_lms_flow_satisfies_the_admin_assignment_gate(): void
    {
        [$teacher, $module, $questions] = $this->moduleWithQuestionBank();
        $lesson = $module->lessons()->first();
        $student = User::factory()->student()->create();
        $student->sessionCreditLedger()->create(['credits' => 5, 'reason' => SessionCreditReason::Adjustment]);

        $this->completeLessons($teacher, $module);
        $this->actingAs($teacher)->post(route('lms.modules.quiz.start', [$module->course, $module]));

        $answers = $questions->mapWithKeys(fn ($q) => [$q->id => $q->correct_answer])->all();
        $this->actingAs($teacher)->post(route('lms.modules.quiz.submit', [$module->course, $module]), ['answers' => $answers]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        Livewire::test(CreateClassSession::class)
            ->fillForm([
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'lesson_id' => $lesson->id,
                'type' => 'physical',
                'scheduled_at' => now()->addDay(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    /**
     * A teacher plus a single-lesson module whose lesson has a published
     * quiz of two questions, both answered by "Correct".
     *
     * @return array{0: User, 1: Module, 2: Collection}
     */
    private function moduleWithQuestionBank(): array
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lesson = Lesson::factory()->for($module)->create();
        $quiz = LessonQuiz::factory()->published()->for($lesson)->create();

        $questions = collect(range(0, 1))->map(fn (int $i) => $quiz->questions()->create([
            'question_text' => "Q{$i}",
            'type' => 'multiple_choice',
            'options' => ['Correct', 'Wrong'],
            'correct_answer' => 'Correct',
            'sort_order' => $i,
        ]));

        return [$teacher, $module, $questions];
    }

    private function completeLessons(User $teacher, Module $module): void
    {
        foreach ($module->lessons as $lesson) {
            $teacher->teacherProfile->lessonCompletions()->firstOrCreate(
                ['lesson_id' => $lesson->id],
                ['completed_at' => now()],
            );
        }
    }
}
