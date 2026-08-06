<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\SessionReview;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SessionFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_student_can_submit_feedback_for_their_own_completed_session(): void
    {
        $student = User::factory()->student()->create();
        $session = ClassSession::factory()->for($student, 'student')
            ->completed()->create(['zoom_meeting_id' => 'feedback-test-1']);

        $this->actingAs($student)
            ->post(route('sessions.feedback.store', $session), ['star_rating' => 5])
            ->assertRedirect();

        $this->assertDatabaseHas('session_reviews', [
            'class_session_id' => $session->id,
            'star_rating' => 5,
            'sentiment' => 'positive',
        ]);

        $this->assertNotNull(SessionReview::first()->student_submitted_at);
    }

    public function test_student_cannot_submit_feedback_for_another_students_session(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $session = ClassSession::factory()->for($otherStudent, 'student')
            ->completed()->create(['zoom_meeting_id' => 'feedback-test-2']);

        $this->actingAs($student)
            ->post(route('sessions.feedback.store', $session), ['star_rating' => 4])
            ->assertForbidden();
    }

    public function test_teachers_cannot_submit_session_feedback(): void
    {
        $teacher = User::factory()->teacher()->create();
        $session = ClassSession::factory()->for($teacher, 'teacher')
            ->completed()->create(['zoom_meeting_id' => 'feedback-test-3']);

        $this->actingAs($teacher)
            ->post(route('sessions.feedback.store', $session), ['star_rating' => 4])
            ->assertForbidden();
    }

    public function test_guests_are_redirected_away_from_submitting_feedback(): void
    {
        $session = ClassSession::factory()->completed()->create(['zoom_meeting_id' => 'feedback-test-4']);

        $this->post(route('sessions.feedback.store', $session), ['star_rating' => 4])
            ->assertRedirect('/login');
    }

    public function test_submitting_feedback_does_not_overwrite_an_existing_admin_qa_review(): void
    {
        $student = User::factory()->student()->create();
        $session = ClassSession::factory()->for($student, 'student')
            ->completed()->create(['zoom_meeting_id' => 'feedback-test-5']);

        SessionReview::factory()->create([
            'class_session_id' => $session->id,
            'flag_severity' => 'red',
            'feedback_score' => 90,
        ]);

        $this->actingAs($student)
            ->post(route('sessions.feedback.store', $session), ['star_rating' => 2]);

        $this->assertDatabaseHas('session_reviews', [
            'class_session_id' => $session->id,
            'flag_severity' => 'red',
            'feedback_score' => 90,
            'star_rating' => 2,
            'sentiment' => 'negative',
        ]);
        $this->assertDatabaseCount('session_reviews', 1);
    }

    public function test_submitting_feedback_twice_updates_rather_than_duplicates(): void
    {
        $student = User::factory()->student()->create();
        $session = ClassSession::factory()->for($student, 'student')
            ->completed()->create(['zoom_meeting_id' => 'feedback-test-6']);

        $this->actingAs($student)->post(route('sessions.feedback.store', $session), ['star_rating' => 2]);
        $this->actingAs($student)->post(route('sessions.feedback.store', $session), ['star_rating' => 5]);

        $this->assertDatabaseCount('session_reviews', 1);
        $this->assertDatabaseHas('session_reviews', ['class_session_id' => $session->id, 'star_rating' => 5]);
    }

    public function test_dashboard_exposes_a_pending_feedback_session_when_unreviewed(): void
    {
        $student = User::factory()->student()->create();
        $session = ClassSession::factory()->for($student, 'student')
            ->completed()->create(['zoom_meeting_id' => 'feedback-test-7']);

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('dashboard/Student')
                ->where('pendingFeedbackSession.id', $session->id)
                ->where('latestReview', null)
            );
    }

    public function test_dashboard_stops_exposing_pending_feedback_once_submitted(): void
    {
        $student = User::factory()->student()->create();
        $session = ClassSession::factory()->for($student, 'student')
            ->completed()->create(['zoom_meeting_id' => 'feedback-test-8']);

        $this->actingAs($student)->post(route('sessions.feedback.store', $session), ['star_rating' => 4]);

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('dashboard/Student')
                ->where('pendingFeedbackSession', null)
                ->where('latestReview.star_rating', 4)
                ->where('latestReview.sentiment', 'positive')
            );
    }
}
