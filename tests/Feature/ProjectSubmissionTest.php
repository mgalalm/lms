<?php

namespace Tests\Feature;

use App\Enums\ProjectSubmissionStatus;
use App\Models\ClassSession;
use App\Models\LessonProject;
use App\Models\ProjectSubmission;
use App\Models\User;
use App\Notifications\ProjectFeedbackSubmitted;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_student_can_submit_a_project_to_a_lesson_that_has_one(): void
    {
        Storage::fake('bunny');

        $student = User::factory()->student()->create();
        $project = LessonProject::factory()->create(['title' => 'Build a Chatbot']);

        $this->actingAs($student)
            ->post(route('lesson-projects.submissions.store', $project), [
                'live_url' => 'https://example.com/my-game',
                'screenshots' => [UploadedFile::fake()->image('screenshot.png')],
            ])
            ->assertRedirect();

        $submission = ProjectSubmission::firstOrFail();

        $this->assertSame($project->id, $submission->lesson_project_id);
        $this->assertSame($student->id, $submission->student_id);
        // The submission inherits the project's title rather than the
        // student typing one — they're submitting their attempt at the
        // assignment, not re-describing it.
        $this->assertSame('Build a Chatbot', $submission->title);
        $this->assertSame('https://example.com/my-game', $submission->live_url);
        $this->assertSame(ProjectSubmissionStatus::Submitted, $submission->status);
        $this->assertCount(1, $submission->getMedia('screenshots'));
    }

    public function test_a_student_cannot_submit_to_a_lesson_that_has_no_project(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('lesson-projects.submissions.store', 999999), [])
            ->assertNotFound();
    }

    public function test_a_student_can_submit_multiple_attempts_for_the_same_project(): void
    {
        $student = User::factory()->student()->create();
        $project = LessonProject::factory()->create();

        $this->actingAs($student)->post(route('lesson-projects.submissions.store', $project), []);
        $this->actingAs($student)->post(route('lesson-projects.submissions.store', $project), []);

        $this->assertSame(2, ProjectSubmission::where('lesson_project_id', $project->id)->where('student_id', $student->id)->count());
    }

    public function test_a_student_can_view_their_own_submission_screenshot(): void
    {
        Storage::fake('bunny');

        $student = User::factory()->student()->create();
        $submission = ProjectSubmission::factory()->create(['student_id' => $student->id]);
        $media = $submission->addMedia(UploadedFile::fake()->image('shot.png'))->toMediaCollection('screenshots');

        $this->actingAs($student)
            ->get(route('project-submissions.media', ['projectSubmission' => $submission, 'media' => $media]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_a_teacher_not_assigned_cannot_view_a_screenshot(): void
    {
        Storage::fake('bunny');

        $teacher = User::factory()->teacher()->create();
        $submission = ProjectSubmission::factory()->create();
        $media = $submission->addMedia(UploadedFile::fake()->image('shot.png'))->toMediaCollection('screenshots');

        $this->actingAs($teacher)
            ->get(route('project-submissions.media', ['projectSubmission' => $submission, 'media' => $media]))
            ->assertForbidden();
    }

    public function test_a_screenshot_must_belong_to_the_requested_submission(): void
    {
        Storage::fake('bunny');

        $student = User::factory()->student()->create();
        $submission = ProjectSubmission::factory()->create(['student_id' => $student->id]);
        $otherSubmission = ProjectSubmission::factory()->create();
        $media = $otherSubmission->addMedia(UploadedFile::fake()->image('shot.png'))->toMediaCollection('screenshots');

        $this->actingAs($student)
            ->get(route('project-submissions.media', ['projectSubmission' => $submission, 'media' => $media]))
            ->assertNotFound();
    }

    public function test_a_teacher_can_view_a_submission_from_a_student_assigned_to_them(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create(['zoom_meeting_id' => 'test-meeting']);

        $submission = ProjectSubmission::factory()->create(['student_id' => $student->id]);

        $this->actingAs($teacher)
            ->getJson(route('teaching.projects.show', $submission))
            ->assertOk()
            ->assertJsonPath('id', $submission->id);
    }

    public function test_a_teacher_cannot_view_a_submission_from_a_student_not_assigned_to_them(): void
    {
        $teacher = User::factory()->teacher()->create();
        $submission = ProjectSubmission::factory()->create();

        $this->actingAs($teacher)
            ->getJson(route('teaching.projects.show', $submission))
            ->assertForbidden();
    }

    public function test_a_teacher_cannot_leave_feedback_for_a_student_not_assigned_to_them(): void
    {
        $teacher = User::factory()->teacher()->create();
        $submission = ProjectSubmission::factory()->create();

        $this->actingAs($teacher)
            ->post(route('teaching.projects.feedback', $submission), [
                'rating' => 5,
                'comment' => 'Great work!',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('project_feedback', ['project_submission_id' => $submission->id]);
    }

    public function test_submitting_feedback_moves_status_to_reviewed_and_notifies_the_student(): void
    {
        Notification::fake();

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create(['zoom_meeting_id' => 'test-meeting']);

        $submission = ProjectSubmission::factory()->create(['student_id' => $student->id]);

        $this->actingAs($teacher)
            ->post(route('teaching.projects.feedback', $submission), [
                'rating' => 4,
                'comment' => 'Nice work — clean structure.',
                'is_featured' => true,
            ])
            ->assertRedirect();

        $submission->refresh();

        $this->assertSame(ProjectSubmissionStatus::Reviewed, $submission->status);
        $this->assertSame($teacher->id, $submission->feedback->teacher_id);
        $this->assertSame(4, $submission->feedback->rating);
        $this->assertTrue($submission->feedback->is_featured);

        Notification::assertSentTo(
            $student,
            ProjectFeedbackSubmitted::class,
            fn (ProjectFeedbackSubmitted $notification) => $notification->submission->is($submission)
        );
    }

    /**
     * The policy itself grants admins view/review access to any
     * submission (defensive/future-proofing, e.g. for Filament or
     * Tinker use) — but note this doesn't mean an admin can reach
     * teaching.projects.* today: those routes require the teacher role,
     * matching this codebase's convention that admins operate through
     * Filament rather than the teacher/student Inertia portals
     * (DashboardController redirects admins to Filament for exactly
     * this reason).
     */
    public function test_the_policy_grants_admins_access_to_any_submission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $submission = ProjectSubmission::factory()->create();

        $this->assertTrue($admin->can('view', $submission));
        $this->assertTrue($admin->can('review', $submission));
    }
}
