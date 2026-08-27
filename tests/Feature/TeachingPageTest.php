<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\ProjectSubmission;
use App\Models\TeacherTask;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeachingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_the_sessions_tab_renders_at_the_teaching_root(): void
    {
        $teacher = User::factory()->teacher()->create();
        ClassSession::factory()->for($teacher, 'teacher')->count(2)->create(['zoom_meeting_id' => 'test-meeting']);

        $this->actingAs($teacher)
            ->get('/teaching')
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Teaching')
                ->where('activeTab', 'sessions')
                ->count('sessions.data', 2)
            );
    }

    public function test_the_projects_tab_renders_at_teaching_projects(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create(['zoom_meeting_id' => 'test-meeting']);
        ProjectSubmission::factory()->for($student, 'student')->create();

        $this->actingAs($teacher)
            ->get(route('teaching.projects.index'))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Teaching')
                ->where('activeTab', 'projects')
                ->count('submissions.data', 1)
            );
    }

    public function test_the_tasks_tab_renders_at_teaching_tasks(): void
    {
        $teacher = User::factory()->teacher()->create();
        TeacherTask::factory()->for($teacher, 'teacher')->create();

        $this->actingAs($teacher)
            ->get(route('teaching.tasks.index'))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Teaching')
                ->where('activeTab', 'tasks')
                ->count('tasks.data', 1)
            );
    }

    public function test_tab_badge_counts_are_present_on_every_tab(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create(['zoom_meeting_id' => 'test-meeting']);
        ProjectSubmission::factory()->for($student, 'student')->create();
        TeacherTask::factory()->for($teacher, 'teacher')->create();

        $this->actingAs($teacher)
            ->get('/teaching')
            ->assertInertia(fn (Assert $page) => $page->where('tabCounts.pendingProjects', 1)
                ->where('tabCounts.pendingTasks', 1)
            );
    }

    public function test_the_old_sessions_schedule_url_redirects_to_teaching(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get('/teaching/sessions-schedule')
            ->assertRedirect('/teaching');
    }

    public function test_students_cannot_view_the_teaching_page(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get('/teaching')
            ->assertForbidden();
    }
}
