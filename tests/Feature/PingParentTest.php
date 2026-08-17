<?php

namespace Tests\Feature;

use App\Enums\SessionType;
use App\Models\ClassSession;
use App\Models\User;
use App\Notifications\ParentPinged;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PingParentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_teacher_can_ping_the_parent_of_their_own_student(): void
    {
        Notification::fake();

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $student->studentProfile->update(['parent_email' => 'parent@example.com', 'parent_name' => 'Pat Guardian']);

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        $this->actingAs($teacher)
            ->post(route('teaching.sessions.ping-parent', $session))
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentOnDemand(
            ParentPinged::class,
            fn (ParentPinged $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'parent@example.com'
                && $notification->classSession->is($session)
        );
    }

    public function test_pinging_a_student_with_no_parent_email_on_file_flashes_an_error(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        Notification::fake();

        $this->actingAs($teacher)
            ->post(route('teaching.sessions.ping-parent', $session))
            ->assertRedirect()
            ->assertSessionHas('error');

        Notification::assertNothingSent();
    }

    public function test_a_teacher_cannot_ping_the_parent_of_another_teachers_student(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $student->studentProfile->update(['parent_email' => 'parent@example.com']);

        $session = ClassSession::factory()
            ->for($otherTeacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        $this->actingAs($teacher)
            ->post(route('teaching.sessions.ping-parent', $session))
            ->assertForbidden();
    }

    public function test_students_cannot_ping_parents(): void
    {
        $student = User::factory()->student()->create();
        $session = ClassSession::factory()->for($student, 'student')->create(['type' => SessionType::Physical]);

        $this->actingAs($student)
            ->post(route('teaching.sessions.ping-parent', $session))
            ->assertForbidden();
    }
}
