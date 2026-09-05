<?php

namespace Tests\Feature;

use App\Enums\SessionCreditReason;
use App\Filament\Resources\ClassSessions\Pages\CreateClassSession;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonQuiz;
use App\Models\LessonVideo;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\TeacherLessonQuizAttempt;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_teacher_can_mark_a_lesson_complete_once_they_have_passed_its_quiz(): void
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lesson = Lesson::factory()->for($module)->create();
        $quiz = LessonQuiz::factory()->published()->for($lesson)->create();

        TeacherLessonQuizAttempt::factory()
            ->for($teacher->teacherProfile)
            ->for($quiz, 'quiz')
            ->passed()
            ->create();

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
        $quiz = LessonQuiz::factory()->published()->for($lesson)->create();

        TeacherLessonQuizAttempt::factory()
            ->for($teacher->teacherProfile)
            ->for($quiz, 'quiz')
            ->passed()
            ->create();

        $this->actingAs($teacher)->post(route('lms.lessons.complete', [$module->course, $lesson]));
        $this->actingAs($teacher)->post(route('lms.lessons.complete', [$module->course, $lesson]));

        $this->assertDatabaseCount('teacher_lesson_completions', 1);
    }

    public function test_teacher_cannot_mark_a_lesson_complete_without_passing_its_quiz(): void
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lesson = Lesson::factory()->for($module)->create();
        LessonQuiz::factory()->published()->for($lesson)->create();

        $this->actingAs($teacher)
            ->post(route('lms.lessons.complete', [$module->course, $lesson]))
            ->assertForbidden();

        $this->assertDatabaseCount('teacher_lesson_completions', 0);
    }

    public function test_teacher_cannot_mark_a_lesson_complete_when_it_has_no_published_quiz(): void
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lesson = Lesson::factory()->for($module)->create();
        // Draft, not published — completion stays locked.
        LessonQuiz::factory()->for($lesson)->create();

        $this->actingAs($teacher)
            ->post(route('lms.lessons.complete', [$module->course, $lesson]))
            ->assertForbidden();

        $this->assertDatabaseCount('teacher_lesson_completions', 0);
    }

    public function test_a_failed_attempt_does_not_unlock_completion(): void
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lesson = Lesson::factory()->for($module)->create();
        $quiz = LessonQuiz::factory()->published()->for($lesson)->create();

        TeacherLessonQuizAttempt::factory()
            ->for($teacher->teacherProfile)
            ->for($quiz, 'quiz')
            ->create(['score' => 50, 'passed' => false]);

        $this->actingAs($teacher)
            ->post(route('lms.lessons.complete', [$module->course, $lesson]))
            ->assertForbidden();
    }

    public function test_a_voided_pass_no_longer_unlocks_completion(): void
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lesson = Lesson::factory()->for($module)->create();
        $quiz = LessonQuiz::factory()->published()->for($lesson)->create();

        TeacherLessonQuizAttempt::factory()
            ->for($teacher->teacherProfile)
            ->for($quiz, 'quiz')
            ->passed()
            ->voided()
            ->create();

        $this->actingAs($teacher)
            ->post(route('lms.lessons.complete', [$module->course, $lesson]))
            ->assertForbidden();
    }

    public function test_teacher_cannot_take_the_quiz_before_completing_all_lessons(): void
    {
        [$teacher, $course, , $quiz] = $this->courseWithQuiz();

        $this->actingAs($teacher)
            ->post(route('lms.quiz.submit', $course), ['answers' => [$quiz->questions->first()->id => 0]])
            ->assertForbidden();
    }

    public function test_teacher_who_passes_the_quiz_becomes_certified(): void
    {
        [$teacher, $course, $lesson, $quiz] = $this->courseWithQuiz();

        $teacher->teacherProfile->lessonCompletions()->create(['lesson_id' => $lesson->id, 'completed_at' => now()]);

        $question = $quiz->questions->first();

        $this->actingAs($teacher)
            ->post(route('lms.quiz.submit', $course), ['answers' => [$question->id => $question->correct_option]])
            ->assertRedirect();

        $this->assertDatabaseHas('teacher_course_completions', [
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'course_id' => $course->id,
            'quiz_passed' => true,
        ]);
        $this->assertTrue($teacher->teacherProfile->isCertifiedForCourse($course));
    }

    public function test_teacher_who_fails_the_quiz_is_not_certified(): void
    {
        [$teacher, $course, $lesson, $quiz] = $this->courseWithQuiz();

        $teacher->teacherProfile->lessonCompletions()->create(['lesson_id' => $lesson->id, 'completed_at' => now()]);

        $question = $quiz->questions->first();
        $wrongOption = $question->correct_option === 0 ? 1 : 0;

        $this->actingAs($teacher)
            ->post(route('lms.quiz.submit', $course), ['answers' => [$question->id => $wrongOption]])
            ->assertRedirect();

        $this->assertFalse($teacher->teacherProfile->fresh()->isCertifiedForCourse($course));
    }

    public function test_completing_the_lms_flow_satisfies_the_admin_assignment_gate(): void
    {
        [$teacher, $course, $lesson, $quiz] = $this->courseWithQuiz();
        $student = User::factory()->student()->create();
        $student->sessionCreditLedger()->create(['credits' => 5, 'reason' => SessionCreditReason::Adjustment]);

        $teacher->teacherProfile->lessonCompletions()->create(['lesson_id' => $lesson->id, 'completed_at' => now()]);
        $question = $quiz->questions->first();

        $this->actingAs($teacher)->post(route('lms.quiz.submit', $course), [
            'answers' => [$question->id => $question->correct_option],
        ]);

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
     * @return array{0: User, 1: Course, 2: Lesson, 3: Quiz}
     */
    private function courseWithQuiz(): array
    {
        $teacher = User::factory()->teacher()->create();
        $module = Module::factory()->create();
        $lesson = Lesson::factory()->for($module)->create();
        $quiz = Quiz::factory()->for($module->course)->create(['passing_score' => 80]);
        QuizQuestion::factory()->for($quiz)->create([
            'options' => ['Correct', 'Wrong'],
            'correct_option' => 0,
        ]);

        return [$teacher, $module->course, $lesson, $quiz->fresh('questions')];
    }
}
