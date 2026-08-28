<?php

namespace Tests\Feature;

use App\Enums\SessionType;
use App\Models\ClassSession;
use App\Models\Lesson;
use App\Models\User;
use App\Notifications\ClassSessionScheduled;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClassSessionNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_scheduling_a_session_notifies_the_teacher_and_student(): void
    {
        Notification::fake();

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        Notification::assertSentTo(
            $teacher,
            ClassSessionScheduled::class,
            fn (ClassSessionScheduled $notification) => $notification->classSession->is($session) && $notification->viewerRole === 'teacher'
        );

        Notification::assertSentTo(
            $student,
            ClassSessionScheduled::class,
            fn (ClassSessionScheduled $notification) => $notification->classSession->is($session) && $notification->viewerRole === 'student'
        );
    }

    public function test_notification_mail_and_array_content_names_the_other_party_and_lesson(): void
    {
        $teacher = User::factory()->teacher()->create(['name' => 'Ms. Ada']);
        $student = User::factory()->student()->create(['name' => 'Grace']);
        $lesson = Lesson::factory()->create(['title' => 'Intro to Loops']);

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->for($lesson, 'lesson')
            ->create(['type' => SessionType::Physical]);

        $teacherNotification = new ClassSessionScheduled($session, 'teacher');
        $mail = $teacherNotification->toMail($teacher);

        $this->assertStringContainsString('Grace', $mail->introLines[0]);
        $this->assertStringContainsString('Intro to Loops', implode(' ', $mail->introLines));

        $studentNotification = new ClassSessionScheduled($session, 'student');
        $data = $studentNotification->toArray($student);

        $this->assertSame($session->id, $data['class_session_id']);
        $this->assertStringContainsString('Ms. Ada', $data['message']);
        $this->assertSame(route('dashboard'), $data['url']);
    }

    public function test_a_user_can_mark_their_own_notification_as_read(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        $teacher->notify(new ClassSessionScheduled($session, 'teacher'));
        $notification = $teacher->notifications()->firstOrFail();

        $this->assertNull($notification->read_at);

        $this->actingAs($teacher)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('teaching.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_user_cannot_mark_someone_elses_notification_as_read(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        $teacher->notify(new ClassSessionScheduled($session, 'teacher'));
        $notification = $teacher->notifications()->firstOrFail();

        $this->actingAs($otherStudent)
            ->post(route('notifications.read', $notification->id))
            ->assertForbidden();
    }

    public function test_marking_all_as_read_only_affects_the_current_users_notifications(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->create(['type' => SessionType::Physical]);

        $teacher->notify(new ClassSessionScheduled($session, 'teacher'));
        $student->notify(new ClassSessionScheduled($session, 'student'));

        $this->actingAs($teacher)
            ->post(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertNotNull($teacher->notifications()->firstOrFail()->read_at);
        $this->assertNull($student->notifications()->firstOrFail()->read_at);
    }
}
