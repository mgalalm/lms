<?php

namespace Tests\Feature;

use App\Console\Commands\GenerateSessionPrepTasksCommand;
use App\Enums\SessionType;
use App\Enums\TeacherTaskType;
use App\Models\ClassSession;
use App\Models\Lesson;
use App\Models\LessonSlideshow;
use App\Models\LessonTutorGuide;
use App\Models\TeacherTask;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherTaskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_the_prep_command_creates_one_task_per_upcoming_session(): void
    {
        $teacher = User::factory()->teacher()->create();
        $lesson = Lesson::factory()->create(['title' => 'Intro to Loops']);
        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($lesson)
            ->create(['type' => SessionType::Physical, 'scheduled_at' => now()->addDays(2)]);

        $this->artisan(GenerateSessionPrepTasksCommand::class)->assertSuccessful();

        $this->assertDatabaseHas('teacher_tasks', [
            'teacher_id' => $teacher->id,
            'class_session_id' => $session->id,
            'type' => TeacherTaskType::SessionPrep->value,
            'title' => 'Review Lesson – Intro to Loops',
        ]);
        $this->assertSame(1, TeacherTask::where('class_session_id', $session->id)->count());
    }

    public function test_the_prep_command_does_not_duplicate_on_a_second_run(): void
    {
        $teacher = User::factory()->teacher()->create();
        ClassSession::factory()->for($teacher, 'teacher')->create(['type' => SessionType::Physical, 'scheduled_at' => now()->addDays(2)]);

        $this->artisan(GenerateSessionPrepTasksCommand::class)->assertSuccessful();
        $this->artisan(GenerateSessionPrepTasksCommand::class)->assertSuccessful();

        $this->assertSame(1, TeacherTask::count());
    }

    public function test_the_prep_command_ignores_past_sessions(): void
    {
        $teacher = User::factory()->teacher()->create();
        ClassSession::factory()->for($teacher, 'teacher')->completed()->create(['type' => SessionType::Physical]);

        $this->artisan(GenerateSessionPrepTasksCommand::class)->assertSuccessful();

        $this->assertSame(0, TeacherTask::count());
    }

    public function test_opening_a_lessons_tutor_guide_completes_its_pending_prep_task(): void
    {
        $teacher = User::factory()->teacher()->create();
        $lesson = Lesson::factory()->create();
        LessonTutorGuide::factory()->for($lesson)->create();
        $task = TeacherTask::factory()->for($teacher, 'teacher')->create([
            'type' => TeacherTaskType::SessionPrep,
            'lesson_id' => $lesson->id,
        ]);

        $this->actingAs($teacher)->get(route('lessons.tutor-guide', $lesson))->assertOk();

        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_opening_a_lessons_tutor_guide_does_not_complete_another_teachers_prep_task(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $lesson = Lesson::factory()->create();
        LessonTutorGuide::factory()->for($lesson)->create();
        $task = TeacherTask::factory()->for($otherTeacher, 'teacher')->create([
            'type' => TeacherTaskType::SessionPrep,
            'lesson_id' => $lesson->id,
        ]);

        $this->actingAs($teacher)->get(route('lessons.tutor-guide', $lesson))->assertOk();

        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_viewing_a_lessons_slideshow_completes_its_pending_prep_task(): void
    {
        $teacher = User::factory()->teacher()->create();
        $lesson = Lesson::factory()->create();
        LessonSlideshow::factory()->for($lesson)->create();
        $task = TeacherTask::factory()->for($teacher, 'teacher')->create([
            'type' => TeacherTaskType::SessionPrep,
            'lesson_id' => $lesson->id,
        ]);

        $this->actingAs($teacher)->post(route('lessons.slideshow.viewed', $lesson))->assertRedirect();

        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_a_teacher_cannot_complete_another_teachers_task(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $task = TeacherTask::factory()->for($otherTeacher, 'teacher')->create(['type' => TeacherTaskType::Manual]);

        $this->actingAs($teacher)
            ->patch(route('teaching.tasks.complete', $task))
            ->assertForbidden();

        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_a_teacher_can_complete_their_own_manual_task(): void
    {
        $teacher = User::factory()->teacher()->create();
        $task = TeacherTask::factory()->for($teacher, 'teacher')->create(['type' => TeacherTaskType::Manual]);

        $this->actingAs($teacher)
            ->patch(route('teaching.tasks.complete', $task))
            ->assertRedirect();

        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_a_teacher_only_sees_their_own_tasks_in_the_tasks_tab(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        TeacherTask::factory()->for($teacher, 'teacher')->create();
        TeacherTask::factory()->for($otherTeacher, 'teacher')->create();

        $this->actingAs($teacher)
            ->get(route('teaching.tasks.index'))
            ->assertInertia(fn ($page) => $page->count('tasks.data', 1));
    }

    public function test_the_meeting_ended_webhook_creates_a_review_task(): void
    {
        config(['services.zoom.webhook_secret' => 'test-secret']);

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create(['title' => 'Intro to Loops']);
        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($student, 'student')
            ->for($lesson)
            ->create(['zoom_meeting_id' => '999888777', 'type' => SessionType::Online]);

        $this->postSignedZoomWebhook([
            'event' => 'meeting.ended',
            'payload' => ['object' => ['id' => '999888777']],
        ])->assertNoContent();

        $this->assertDatabaseHas('teacher_tasks', [
            'teacher_id' => $teacher->id,
            'class_session_id' => $session->id,
            'type' => TeacherTaskType::SessionReview->value,
        ]);
    }

    public function test_the_meeting_ended_webhook_does_not_duplicate_a_review_task_on_retry(): void
    {
        config(['services.zoom.webhook_secret' => 'test-secret']);

        $session = ClassSession::factory()->create(['zoom_meeting_id' => '999888777', 'type' => SessionType::Online]);

        $payload = ['event' => 'meeting.ended', 'payload' => ['object' => ['id' => '999888777']]];
        $this->postSignedZoomWebhook($payload)->assertNoContent();
        $this->postSignedZoomWebhook($payload)->assertNoContent();

        $this->assertSame(1, TeacherTask::where('class_session_id', $session->id)->where('type', TeacherTaskType::SessionReview)->count());
    }

    /**
     * Post a Zoom webhook payload signed the way Zoom signs real requests
     * (mirrors ClassSessionZoomIntegrationTest's private helper).
     */
    private function postSignedZoomWebhook(array $payload)
    {
        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = 'v0='.hash_hmac('sha256', "v0:{$timestamp}:{$body}", 'test-secret');

        return $this->postJson('/webhooks/zoom', $payload, [
            'x-zm-signature' => $signature,
            'x-zm-request-timestamp' => $timestamp,
        ]);
    }
}
