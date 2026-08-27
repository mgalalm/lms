<?php

namespace Tests\Feature;

use App\Enums\SessionStatus;
use App\Models\ClassSession;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_student_sees_the_student_dashboard_with_an_empty_state(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('dashboard/Student')
                ->where('nextSession', null)
                ->where('currentLesson', null)
                ->where('progress', null)
            );
    }

    public function test_student_dashboard_exposes_the_current_lesson_and_module_progress(): void
    {
        $student = User::factory()->student()->create();
        $module = Module::factory()->create(['title' => 'M1: Intro to Scratch']);
        $completedLesson = Lesson::factory()->for($module)->create();
        $upcomingLesson = Lesson::factory()->for($module)->create();

        ClassSession::factory()
            ->for($student, 'student')
            ->for($completedLesson, 'lesson')
            ->completed()
            ->create(['zoom_meeting_id' => 'test-meeting-completed']);

        $upcomingSession = ClassSession::factory()
            ->for($student, 'student')
            ->for($upcomingLesson, 'lesson')
            ->create(['zoom_meeting_id' => 'test-meeting-upcoming']);

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('dashboard/Student')
                ->where('nextSession.id', $upcomingSession->id)
                ->where('currentLesson.id', $upcomingLesson->id)
                ->where('currentLesson.module_title', 'M1: Intro to Scratch')
                ->where('progress.module_title', 'M1: Intro to Scratch')
                ->where('progress.completed', 1)
                ->where('progress.total', 2)
            );
    }

    public function test_student_dashboard_still_shows_a_session_that_recently_started(): void
    {
        $student = User::factory()->student()->create();
        $session = ClassSession::factory()
            ->for($student, 'student')
            ->create([
                'status' => SessionStatus::Scheduled,
                'scheduled_at' => now()->subMinutes(15),
                'zoom_meeting_id' => 'test-meeting-live',
            ]);

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('dashboard/Student')
                ->where('nextSession.id', $session->id)
            );
    }

    public function test_teacher_dashboard_still_shows_a_session_that_recently_started(): void
    {
        $teacher = User::factory()->teacher()->create();
        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->create([
                'status' => SessionStatus::Scheduled,
                'scheduled_at' => now()->subMinutes(15),
                'zoom_meeting_id' => 'test-meeting-live',
            ]);

        $this->actingAs($teacher)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('dashboard/Teacher')
                ->where('nextSession.id', $session->id)
            );
    }

    public function test_dashboard_stops_showing_a_session_once_the_zoom_webhook_marks_it_completed(): void
    {
        $student = User::factory()->student()->create();
        ClassSession::factory()
            ->for($student, 'student')
            ->create([
                'status' => SessionStatus::Completed,
                'scheduled_at' => now()->subMinutes(15),
                'zoom_meeting_id' => 'test-meeting-ended',
            ]);

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('dashboard/Student')
                ->where('nextSession', null)
            );
    }

    public function test_dashboard_stops_showing_a_session_that_started_too_long_ago(): void
    {
        $student = User::factory()->student()->create();
        ClassSession::factory()
            ->for($student, 'student')
            ->create([
                // Still Scheduled — simulates a missed/failed Zoom
                // meeting.ended webhook — but well past any plausible
                // session length, so the live-window fallback still
                // stops it from being surfaced as "next" forever.
                'status' => SessionStatus::Scheduled,
                'scheduled_at' => now()->subMinutes(90),
                'zoom_meeting_id' => 'test-meeting-stale',
            ]);

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('dashboard/Student')
                ->where('nextSession', null)
            );
    }

    public function test_student_dashboard_exposes_the_join_url_but_not_the_start_url(): void
    {
        $student = User::factory()->student()->create();
        $session = ClassSession::factory()
            ->for($student, 'student')
            ->create([
                'zoom_meeting_id' => 'test-meeting',
                'zoom_join_url' => 'https://zoom.us/j/attendee',
                'zoom_start_url' => 'https://zoom.us/s/host',
            ]);

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('dashboard/Student')
                ->where('nextSession.id', $session->id)
                ->where('nextSession.join_url', 'https://zoom.us/j/attendee')
                ->missing('nextSession.start_url')
            );
    }

    public function test_teacher_dashboard_exposes_the_start_url_but_not_the_join_url(): void
    {
        $teacher = User::factory()->teacher()->create();
        $session = ClassSession::factory()
            ->for($teacher, 'teacher')
            ->create([
                'zoom_meeting_id' => 'test-meeting',
                'zoom_join_url' => 'https://zoom.us/j/attendee',
                'zoom_start_url' => 'https://zoom.us/s/host',
            ]);

        $this->actingAs($teacher)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('dashboard/Teacher')
                ->where('nextSession.id', $session->id)
                ->where('nextSession.start_url', 'https://zoom.us/s/host')
                ->missing('nextSession.join_url')
            );
    }

    public function test_the_student_dashboard_never_exposes_a_recap_url(): void
    {
        // Recaps are for teacher review, not student rewatch (see
        // ClassSessionPolicy::viewRecap()) — the resource must never leak
        // the URL to a student regardless of viewer identity.
        $student = User::factory()->student()->create();
        ClassSession::factory()
            ->for($student, 'student')
            ->create(['zoom_meeting_id' => 'test-meeting', 'zoom_recap_url' => 'https://zoom.us/rec/play/abc123']);

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->missing('nextSession.recap_url'));
    }

    public function test_sessions_schedule_exposes_the_recap_url_to_the_teacher(): void
    {
        $teacher = User::factory()->teacher()->create();
        ClassSession::factory()
            ->for($teacher, 'teacher')
            ->create(['zoom_meeting_id' => 'test-meeting', 'zoom_recap_url' => 'https://zoom.us/rec/play/abc123']);

        $this->actingAs($teacher)
            ->get(route('teaching.sessions-schedule'))
            ->assertInertia(fn (Assert $page) => $page->where('sessions.data.0.recap_url', 'https://zoom.us/rec/play/abc123'));
    }

    public function test_users_without_a_student_or_teacher_role_see_the_generic_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
    }

    public function test_admins_are_redirected_from_the_dashboard_to_the_admin_panel(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertRedirect(route('filament.admin.pages.dashboard'));
    }

    public function test_teacher_can_view_the_sessions_schedule(): void
    {
        $teacher = User::factory()->teacher()->create();
        ClassSession::factory()->for($teacher, 'teacher')->count(3)->create(['zoom_meeting_id' => 'test-meeting']);

        $this->actingAs($teacher)
            ->get(route('teaching.sessions-schedule'))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Teaching')
                ->where('activeTab', 'sessions')
                ->where('filter', 'upcoming')
                ->count('sessions.data', 3)
            );
    }

    public function test_sessions_schedule_only_includes_the_teachers_own_sessions(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        ClassSession::factory()->for($teacher, 'teacher')->create(['zoom_meeting_id' => 'test-meeting-1']);
        ClassSession::factory()->for($otherTeacher, 'teacher')->create(['zoom_meeting_id' => 'test-meeting-2']);

        $this->actingAs($teacher)
            ->get(route('teaching.sessions-schedule'))
            ->assertInertia(fn (Assert $page) => $page->count('sessions.data', 1));
    }

    public function test_sessions_schedule_past_filter_only_returns_completed_or_elapsed_sessions(): void
    {
        $teacher = User::factory()->teacher()->create();
        ClassSession::factory()->for($teacher, 'teacher')->completed()->create(['zoom_meeting_id' => 'test-meeting-1']);
        ClassSession::factory()->for($teacher, 'teacher')->create([
            'status' => SessionStatus::Scheduled,
            'zoom_meeting_id' => 'test-meeting-2',
        ]);

        $this->actingAs($teacher)
            ->get(route('teaching.sessions-schedule', ['filter' => 'past']))
            ->assertInertia(fn (Assert $page) => $page->count('sessions.data', 1));
    }

    public function test_students_cannot_view_the_teacher_sessions_schedule(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('teaching.sessions-schedule'))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_away_from_the_sessions_schedule(): void
    {
        $this->get(route('teaching.sessions-schedule'))
            ->assertRedirect('/login');
    }
}
