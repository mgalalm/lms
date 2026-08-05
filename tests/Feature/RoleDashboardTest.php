<?php

namespace Tests\Feature;

use App\Enums\SessionStatus;
use App\Models\ClassSession;
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
            ->assertInertia(fn (Assert $page) => $page->component('dashboard/Student')->where('nextSession', null));
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

    public function test_users_without_a_student_or_teacher_role_see_the_generic_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('Dashboard'));
    }

    public function test_teacher_can_view_the_sessions_schedule(): void
    {
        $teacher = User::factory()->teacher()->create();
        ClassSession::factory()->for($teacher, 'teacher')->count(3)->create(['zoom_meeting_id' => 'test-meeting']);

        $this->actingAs($teacher)
            ->get(route('teaching.sessions-schedule'))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/SessionsSchedule')
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
