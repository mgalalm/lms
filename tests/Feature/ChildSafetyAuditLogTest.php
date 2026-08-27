<?php

namespace Tests\Feature;

use App\Enums\MessageModerationStatus;
use App\Enums\SessionType;
use App\Models\Channel;
use App\Models\ClassSession;
use App\Models\Message;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ChildSafetyAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_viewing_a_session_recording_logs_the_access(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical, 'zoom_recap_url' => 'https://zoom.us/rec/play/abc123']);

        $this->actingAs($teacher)
            ->get(route('sessions.recap', $session))
            ->assertRedirect('https://zoom.us/rec/play/abc123');

        $activity = Activity::latest('id')->first();

        $this->assertNotNull($activity);
        $this->assertSame('Viewed session recording', $activity->description);
        $this->assertTrue($activity->causer->is($teacher));
        $this->assertTrue($activity->subject->is($session));
        $this->assertSame('127.0.0.1', $activity->properties['ip'] ?? null);
    }

    public function test_a_student_cannot_view_their_own_recording(): void
    {
        // Recaps are for teacher review, not student rewatch — this is a
        // deliberate policy change (see ClassSessionPolicy::viewRecap()),
        // not an oversight.
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical, 'zoom_recap_url' => 'https://zoom.us/rec/play/abc123']);

        $this->actingAs($student)
            ->get(route('sessions.recap', $session))
            ->assertForbidden();

        $this->assertNull(Activity::first());
    }

    public function test_a_student_cannot_view_another_students_recording(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical, 'zoom_recap_url' => 'https://zoom.us/rec/play/abc123']);

        $this->actingAs($otherStudent)
            ->get(route('sessions.recap', $session))
            ->assertForbidden();

        $this->assertNull(Activity::first());
    }

    public function test_viewing_a_session_with_no_recording_returns_not_found(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical, 'zoom_recap_url' => null]);

        $this->actingAs($teacher)
            ->get(route('sessions.recap', $session))
            ->assertNotFound();

        $this->assertNull(Activity::first());
    }

    public function test_a_teacher_opening_a_students_chat_history_logs_the_access(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = Channel::findOrCreateDirect($teacher, $student);

        $this->actingAs($teacher)
            ->get(route('community.show', $channel))
            ->assertOk();

        $activity = Activity::latest('id')->first();

        $this->assertNotNull($activity);
        $this->assertSame('Viewed student chat history', $activity->description);
        $this->assertTrue($activity->causer->is($teacher));
        $this->assertTrue($activity->subject->is($channel));
        $this->assertSame([$student->id], $activity->properties['student_ids'] ?? null);
    }

    public function test_a_student_opening_their_own_chat_history_is_not_logged(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $channel = Channel::findOrCreateDirect($teacher, $student);

        $this->actingAs($student)
            ->get(route('community.show', $channel))
            ->assertOk();

        $this->assertNull(Activity::first());
    }

    public function test_reviewing_a_flagged_message_logs_the_access(): void
    {
        $student = User::factory()->student()->create();
        $message = Message::withoutEvents(fn () => Message::factory()->create([
            'user_id' => $student->id,
            'moderation_status' => MessageModerationStatus::Flagged,
        ]));

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get("/admin/flagged-messages/{$message->id}")
            ->assertOk();

        $activity = Activity::latest('id')->first();

        $this->assertNotNull($activity);
        $this->assertSame('Reviewed flagged message', $activity->description);
        $this->assertTrue($activity->causer->is($admin));
        $this->assertTrue($activity->subject->is($message));
    }
}
