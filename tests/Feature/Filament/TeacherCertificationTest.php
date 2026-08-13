<?php

namespace Tests\Feature\Filament;

use App\Enums\SessionCreditReason;
use App\Enums\SessionType;
use App\Filament\Resources\ClassSessions\Pages\CreateClassSession;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\RelationManagers\TeacherCompletionsRelationManager;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\TeacherCourseCompletion;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeacherCertificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_uncertified_teacher_is_certified_for_course_returns_false(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create();

        $this->assertFalse($teacher->teacherProfile->isCertifiedForCourse($course));
    }

    public function test_teacher_who_completed_the_course_but_not_the_quiz_is_not_certified(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create();

        TeacherCourseCompletion::factory()->create([
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'course_id' => $course->id,
            'completed_at' => now(),
            'quiz_passed' => false,
        ]);

        $this->assertFalse($teacher->teacherProfile->isCertifiedForCourse($course));
    }

    public function test_teacher_who_completed_the_course_and_passed_the_quiz_is_certified(): void
    {
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create();

        TeacherCourseCompletion::factory()->certified()->create([
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'course_id' => $course->id,
        ]);

        $this->assertTrue($teacher->teacherProfile->isCertifiedForCourse($course));
    }

    public function test_admin_can_record_a_teacher_certification_via_the_relation_manager(): void
    {
        $admin = $this->admin();
        $teacher = User::factory()->teacher()->create();
        $course = Course::factory()->create();
        $this->actingAs($admin);

        Livewire::test(TeacherCompletionsRelationManager::class, [
            'ownerRecord' => $course,
            'pageClass' => EditCourse::class,
        ])
            ->assertOk()
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'teacher_profile_id' => $teacher->teacherProfile->id,
                'completed_at' => now(),
                'quiz_passed' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('teacher_course_completions', [
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'course_id' => $course->id,
            'quiz_passed' => true,
        ]);
    }

    public function test_admin_cannot_assign_an_uncertified_teacher_to_a_class_session(): void
    {
        $admin = $this->admin();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create();
        $student->sessionCreditLedger()->create(['credits' => 5, 'reason' => SessionCreditReason::Adjustment]);

        $this->actingAs($admin);

        Livewire::test(CreateClassSession::class)
            ->fillForm([
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'lesson_id' => $lesson->id,
                'type' => SessionType::Physical->value,
                'scheduled_at' => now()->addDay(),
            ])
            ->call('create')
            ->assertHasFormErrors(['teacher_id']);

        $this->assertDatabaseMissing('class_sessions', [
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_admin_can_assign_a_certified_teacher_to_a_class_session(): void
    {
        $admin = $this->admin();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create();

        TeacherCourseCompletion::factory()->certified()->create([
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'course_id' => $lesson->module->course_id,
        ]);
        $student->sessionCreditLedger()->create(['credits' => 5, 'reason' => SessionCreditReason::Adjustment]);

        $this->actingAs($admin);

        Livewire::test(CreateClassSession::class)
            ->fillForm([
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'lesson_id' => $lesson->id,
                'type' => SessionType::Physical->value,
                'scheduled_at' => now()->addDay(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('class_sessions', [
            'teacher_id' => $teacher->id,
            'lesson_id' => $lesson->id,
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
