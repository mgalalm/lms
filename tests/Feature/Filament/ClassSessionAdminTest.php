<?php

namespace Tests\Feature\Filament;

use App\Enums\ChannelType;
use App\Enums\SessionType;
use App\Filament\Resources\ClassSessions\Pages\CreateClassSession;
use App\Models\Channel;
use App\Models\ClassSession;
use App\Models\Lesson;
use App\Models\TeacherCourseCompletion;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClassSessionAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_assign_a_teacher_and_student_to_a_session(): void
    {
        $admin = $this->admin();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create();

        TeacherCourseCompletion::factory()->certified()->create([
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'course_id' => $lesson->module->course_id,
        ]);

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
            'student_id' => $student->id,
            'lesson_id' => $lesson->id,
            'type' => SessionType::Physical->value,
        ]);
    }

    public function test_creating_a_session_creates_a_direct_channel_between_teacher_and_student(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $this->assertDatabaseCount('channels', 0);

        ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        $channel = Channel::where('type', ChannelType::Direct)->first();

        $this->assertNotNull($channel);
        $this->assertTrue($channel->users->contains($teacher));
        $this->assertTrue($channel->users->contains($student));
    }

    public function test_a_second_session_between_the_same_pair_reuses_the_existing_channel(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        $this->assertDatabaseCount('channels', 1);
    }

    public function test_teacher_select_options_only_include_teachers(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $teacherIds = User::role('teacher')->pluck('id');

        $this->assertTrue($teacherIds->contains($teacher->id));
        $this->assertFalse($teacherIds->contains($student->id));
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
