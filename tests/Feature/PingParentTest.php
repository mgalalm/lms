<?php

namespace Tests\Feature;

use App\Enums\SessionType;
use App\Enums\SettingKey;
use App\Models\ClassSession;
use App\Models\ParentContact;
use App\Models\ParentPingLog;
use App\Models\Setting;
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
        ParentContact::factory()->for($student, 'student')->emailVerified()->create([
            'email' => 'parent@example.com',
            'name' => 'Pat Guardian',
        ]);

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        $this->actingAs($teacher)
            ->post(route('teaching.sessions.ping-parent', $session), ['message' => 'Please make sure they log in a bit earlier next time.'])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentOnDemand(
            ParentPinged::class,
            fn (ParentPinged $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'parent@example.com'
                && $notification->classSession->is($session)
                && $notification->message === 'Please make sure they log in a bit earlier next time.'
        );

        $this->assertDatabaseHas('parent_ping_logs', [
            'student_id' => $student->id,
            'channel' => 'email',
            'status' => 'sent',
        ]);
    }

    public function test_pinging_without_a_message_fails_validation(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        Notification::fake();

        $this->actingAs($teacher)
            ->post(route('teaching.sessions.ping-parent', $session), ['message' => ''])
            ->assertSessionHasErrors('message');

        Notification::assertNothingSent();
    }

    public function test_pinging_a_student_with_no_parent_contact_on_file_flashes_an_error(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        Notification::fake();

        $this->actingAs($teacher)
            ->post(route('teaching.sessions.ping-parent', $session), ['message' => 'Just checking in.'])
            ->assertRedirect()
            ->assertSessionHas('error');

        Notification::assertNothingSent();
    }

    public function test_a_teacher_cannot_ping_the_parent_of_another_teachers_student(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        ParentContact::factory()->for($student, 'student')->emailVerified()->create(['email' => 'parent@example.com']);

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

    public function test_ping_falls_back_to_verified_email_when_sms_preferred_but_phone_unverified(): void
    {
        Notification::fake();

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $contact = ParentContact::factory()->for($student, 'student')
            ->emailVerified()
            ->withPhone('+353871234567') // unverified
            ->create(['email' => 'parent@example.com']);
        // urgent_ping defaults to sms — phone is unverified, so this should
        // fall back to the verified email instead.

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        $this->actingAs($teacher)
            ->post(route('teaching.sessions.ping-parent', $session), ['message' => 'Checking in.'])
            ->assertSessionHas('success');

        Notification::assertSentOnDemand(
            ParentPinged::class,
            fn (ParentPinged $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'parent@example.com'
        );

        $this->assertDatabaseHas('parent_ping_logs', [
            'parent_contact_id' => $contact->id,
            'channel' => 'email',
            'status' => 'sent',
        ]);
    }

    public function test_ping_surfaces_no_verified_contact_in_the_audit_log(): void
    {
        Notification::fake();

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        // Neither email nor phone verified.
        ParentContact::factory()->for($student, 'student')->create(['email' => 'parent@example.com', 'email_verified_at' => null]);

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        $this->actingAs($teacher)
            ->post(route('teaching.sessions.ping-parent', $session), ['message' => 'Checking in.'])
            ->assertSessionHas('success');

        Notification::assertSentOnDemandTimes(ParentPinged::class, 0);

        $this->assertDatabaseHas('parent_ping_logs', [
            'student_id' => $student->id,
            'status' => 'no_verified_contact',
        ]);
    }

    public function test_ping_is_rate_limited_per_student_within_30_minutes(): void
    {
        Notification::fake();

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        ParentContact::factory()->for($student, 'student')->emailVerified()->create(['email' => 'parent@example.com']);

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        $this->actingAs($teacher)
            ->post(route('teaching.sessions.ping-parent', $session), ['message' => 'First ping.'])
            ->assertSessionHas('success');

        $this->actingAs($teacher)
            ->post(route('teaching.sessions.ping-parent', $session), ['message' => 'Second ping.'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('parent_ping_logs', 1);
    }

    public function test_ping_is_rate_limited_by_the_per_teacher_daily_cap(): void
    {
        Notification::fake();
        Setting::set(SettingKey::ParentPingDailyCapPerTeacher, 1);

        $teacher = User::factory()->teacher()->create();
        $studentA = User::factory()->student()->create();
        $studentB = User::factory()->student()->create();
        ParentContact::factory()->for($studentA, 'student')->emailVerified()->create(['email' => 'a@example.com']);
        ParentContact::factory()->for($studentB, 'student')->emailVerified()->create(['email' => 'b@example.com']);

        $sessionA = ClassSession::factory()->for($teacher, 'teacher')->for($studentA, 'student')->create(['type' => SessionType::Physical]);
        $sessionB = ClassSession::factory()->for($teacher, 'teacher')->for($studentB, 'student')->create(['type' => SessionType::Physical]);

        $this->actingAs($teacher)
            ->post(route('teaching.sessions.ping-parent', $sessionA), ['message' => 'First.'])
            ->assertSessionHas('success');

        $this->actingAs($teacher)
            ->post(route('teaching.sessions.ping-parent', $sessionB), ['message' => 'Second.'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('parent_ping_logs', 1);
    }

    /**
     * Regression: the pre-refactor behavior (email straight to
     * parent_email, no verification gate) still works end to end once
     * the contact point is verified — the refactor changed the plumbing,
     * not the outcome for the already-working case.
     */
    public function test_existing_email_only_path_still_works_after_refactor(): void
    {
        Notification::fake();

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        ParentContact::factory()->for($student, 'student')->emailVerified()->create(['email' => 'parent@example.com']);

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        $this->actingAs($teacher)
            ->post(route('teaching.sessions.ping-parent', $session), ['message' => 'Hello.'])
            ->assertSessionHas('success');

        Notification::assertSentOnDemandTimes(ParentPinged::class, 1);
        $this->assertSame(1, ParentPingLog::where('status', 'sent')->count());
    }
}
