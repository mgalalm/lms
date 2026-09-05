<?php

namespace Tests\Feature;

use App\Enums\SessionType;
use App\Enums\TeacherTaskType;
use App\Models\ClassSession;
use App\Models\Lesson;
use App\Models\ProjectSubmission;
use App\Models\SupportTicket;
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

    /**
     * @return array<int, mixed>
     */
    private function groupKeys(Assert $page): array
    {
        $feed = $page->toArray()['props']['feed'] ?? [];

        return array_column($feed, 'key');
    }

    public function test_an_overdue_task_appears_in_the_needs_attention_group(): void
    {
        $teacher = User::factory()->teacher()->create();
        TeacherTask::factory()->for($teacher, 'teacher')->overdue()->create(['type' => TeacherTaskType::Manual]);

        $this->actingAs($teacher)
            ->get(route('teaching.index'))
            ->assertInertia(function (Assert $page) {
                $this->assertContains('needs_attention', $this->groupKeys($page));
            });
    }

    public function test_a_session_scheduled_today_appears_in_the_today_group(): void
    {
        // Anchored mid-day — "+2 hours" near midnight could otherwise
        // roll into tomorrow and flip this into the this_week group.
        $this->travelTo(now()->setTime(10, 0));

        $teacher = User::factory()->teacher()->create();
        ClassSession::factory()->for($teacher, 'teacher')->create([
            'type' => SessionType::Physical,
            'scheduled_at' => now()->addHours(2),
        ]);

        $this->actingAs($teacher)
            ->get(route('teaching.index'))
            ->assertInertia(function (Assert $page) {
                $this->assertContains('today', $this->groupKeys($page));
            });
    }

    public function test_a_fresh_pending_project_appears_in_the_awaiting_review_group(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create(['zoom_meeting_id' => 'test-meeting']);
        ProjectSubmission::factory()->for($student, 'student')->create(['submitted_at' => now()]);

        $this->actingAs($teacher)
            ->get(route('teaching.index'))
            ->assertInertia(function (Assert $page) {
                $this->assertContains('awaiting_review', $this->groupKeys($page));
            });
    }

    public function test_a_session_in_three_days_appears_in_the_this_week_group(): void
    {
        $teacher = User::factory()->teacher()->create();
        ClassSession::factory()->for($teacher, 'teacher')->create([
            'type' => SessionType::Physical,
            'scheduled_at' => now()->addDays(3),
        ]);

        $this->actingAs($teacher)
            ->get(route('teaching.index'))
            ->assertInertia(function (Assert $page) {
                $this->assertContains('this_week', $this->groupKeys($page));
            });
    }

    public function test_a_project_submitted_past_the_overdue_window_appears_in_needs_attention(): void
    {
        config(['teaching.project_review_overdue_days' => 3]);

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create(['zoom_meeting_id' => 'test-meeting']);
        ProjectSubmission::factory()->for($student, 'student')->create(['submitted_at' => now()->subDays(5)]);

        $this->actingAs($teacher)
            ->get(route('teaching.index'))
            ->assertInertia(function (Assert $page) {
                $this->assertContains('needs_attention', $this->groupKeys($page));
                $this->assertNotContains('awaiting_review', $this->groupKeys($page));
            });
    }

    public function test_an_open_ticket_appears_in_the_needs_attention_group(): void
    {
        $teacher = User::factory()->teacher()->create();
        SupportTicket::factory()->for($teacher, 'teacher')->create();

        $this->actingAs($teacher)
            ->get(route('teaching.index'))
            ->assertInertia(function (Assert $page) {
                $this->assertContains('needs_attention', $this->groupKeys($page));
            });
    }

    public function test_a_resolved_ticket_does_not_appear_in_the_feed(): void
    {
        $teacher = User::factory()->teacher()->create();
        SupportTicket::factory()->for($teacher, 'teacher')->resolved()->create();

        $this->actingAs($teacher)
            ->get(route('teaching.index'))
            ->assertInertia(fn (Assert $page) => $page->where('chipCounts.tickets', 0));
    }

    public function test_search_matches_a_ticket_by_subject(): void
    {
        $teacher = User::factory()->teacher()->create();
        SupportTicket::factory()->for($teacher, 'teacher')->create(['subject' => 'Unique Frozen Screen Issue']);
        SupportTicket::factory()->for($teacher, 'teacher')->create(['subject' => 'Something else entirely']);

        $this->actingAs($teacher)
            ->get(route('teaching.index', ['search' => 'frozen screen']))
            ->assertInertia(fn (Assert $page) => $page->where('chipCounts.tickets', 1));
    }

    public function test_empty_groups_are_omitted_from_the_feed(): void
    {
        $teacher = User::factory()->teacher()->create();
        // Nothing created — no sessions, projects, or tasks at all.

        $this->actingAs($teacher)
            ->get(route('teaching.index'))
            ->assertInertia(function (Assert $page) {
                $this->assertSame([], $this->groupKeys($page));
            });
    }

    public function test_summary_counts_reflect_overdue_and_today_items(): void
    {
        // Anchored mid-day — "+1 hour" near midnight could otherwise roll
        // into tomorrow and drop the session out of "today".
        $this->travelTo(now()->setTime(10, 0));

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        // Not the factory's overdue() state — that uses Faker's
        // dateTimeBetween(), which is anchored to the real wall clock, not
        // the frozen now() above. Depending on the real time-of-day, that
        // could land after our frozen "now" and stop looking overdue.
        TeacherTask::factory()->for($teacher, 'teacher')->create([
            'type' => TeacherTaskType::Manual,
            'due_at' => now()->subDay(),
        ]);
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create([
            'zoom_meeting_id' => 'test-meeting',
            'scheduled_at' => now()->addHours(1),
        ]);
        ProjectSubmission::factory()->for($student, 'student')->create(['submitted_at' => now()]);

        $this->actingAs($teacher)
            ->get(route('teaching.index'))
            ->assertInertia(fn (Assert $page) => $page->where('summary.overdue', 1)
                ->where('summary.sessionsToday', 1)
                ->where('summary.projectsAwaitingReview', 1)
            );
    }

    public function test_search_matches_a_task_by_title(): void
    {
        $teacher = User::factory()->teacher()->create();
        TeacherTask::factory()->for($teacher, 'teacher')->create(['title' => 'Prepare unique whiteboard materials']);
        TeacherTask::factory()->for($teacher, 'teacher')->create(['title' => 'Something else entirely']);

        $this->actingAs($teacher)
            ->get(route('teaching.index', ['search' => 'whiteboard']))
            ->assertInertia(fn (Assert $page) => $page->where('chipCounts.tasks', 1));
    }

    public function test_a_session_prep_tasks_lesson_includes_course_context_for_the_materials_menu(): void
    {
        $teacher = User::factory()->teacher()->create();
        $lesson = Lesson::factory()->create(['title' => 'Sprites and the Stage']);
        $lesson->load('module.course');

        TeacherTask::factory()->for($teacher, 'teacher')->for($lesson)->create([
            'type' => TeacherTaskType::SessionPrep,
            'due_at' => now()->addHour(),
        ]);

        $this->actingAs($teacher)
            ->get(route('teaching.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('feed.0.items.0.task.lesson.id', $lesson->id)
                ->where('feed.0.items.0.task.lesson.module.course.id', $lesson->module->course->id)
            );
    }

    public function test_search_matches_a_project_by_title(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create(['zoom_meeting_id' => 'test-meeting']);
        ProjectSubmission::factory()->for($student, 'student')->create(['title' => 'Galactic Adventure Game']);

        $this->actingAs($teacher)
            ->get(route('teaching.index', ['search' => 'galactic']))
            ->assertInertia(fn (Assert $page) => $page->where('chipCounts.projects', 1));
    }

    public function test_search_matches_a_session_by_lesson_title(): void
    {
        $teacher = User::factory()->teacher()->create();
        $lesson = Lesson::factory()->create(['title' => 'Sprites and the Stage']);
        ClassSession::factory()->for($teacher, 'teacher')->for($lesson)->create(['zoom_meeting_id' => 'test-meeting']);

        $this->actingAs($teacher)
            ->get(route('teaching.index', ['search' => 'sprites']))
            ->assertInertia(fn (Assert $page) => $page->where('chipCounts.sessions', 1));
    }

    public function test_old_tabbed_page_urls_redirect_to_the_unified_feed(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get('/teaching/sessions-schedule')->assertRedirect('/teaching');
        $this->actingAs($teacher)->get('/teaching/projects')->assertRedirect('/teaching');
        $this->actingAs($teacher)->get('/teaching/tasks')->assertRedirect('/teaching');
        $this->actingAs($teacher)->get('/teaching/history')->assertRedirect('/teaching/archive');
    }

    public function test_the_archive_page_lists_past_sessions_reviewed_projects_completed_tasks_and_resolved_tickets(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->completed()->create(['zoom_meeting_id' => 'test-meeting']);
        ProjectSubmission::factory()->for($student, 'student')->reviewed()->create();
        TeacherTask::factory()->for($teacher, 'teacher')->completed()->create(['type' => TeacherTaskType::Manual]);
        SupportTicket::factory()->for($teacher, 'teacher')->resolved()->create();

        $this->actingAs($teacher)
            ->get(route('teaching.archive'))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/TeachingArchive')
                ->count('pastSessions.data', 1)
                ->count('reviewedProjects.data', 1)
                ->count('completedTasks.data', 1)
                ->count('resolvedTickets.data', 1)
            );
    }

    public function test_archived_past_sessions_can_be_searched_by_student_name(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create(['name' => 'Alice Example']);
        $otherStudent = User::factory()->student()->create(['name' => 'Bob Example']);

        $aliceSession = ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')
            ->completed()->create(['zoom_meeting_id' => 'archive-test-1']);
        ClassSession::factory()->for($teacher, 'teacher')->for($otherStudent, 'student')
            ->completed()->create(['zoom_meeting_id' => 'archive-test-2']);

        $this->actingAs($teacher)
            ->get(route('teaching.archive', ['search' => 'Alice']))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/TeachingArchive')
                ->count('pastSessions.data', 1)
                ->where('pastSessions.data.0.id', $aliceSession->id)
            );
    }

    public function test_archived_reviewed_projects_can_be_searched_by_student_name(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create(['name' => 'Alice Example']);
        $otherStudent = User::factory()->student()->create(['name' => 'Bob Example']);
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create(['zoom_meeting_id' => 'archive-test-3']);
        ClassSession::factory()->for($teacher, 'teacher')->for($otherStudent, 'student')->create(['zoom_meeting_id' => 'archive-test-4']);

        $aliceProject = ProjectSubmission::factory()->for($student, 'student')->reviewed()->create();
        ProjectSubmission::factory()->for($otherStudent, 'student')->reviewed()->create();

        $this->actingAs($teacher)
            ->get(route('teaching.archive', ['search' => 'Alice']))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/TeachingArchive')
                ->count('reviewedProjects.data', 1)
                ->where('reviewedProjects.data.0.id', $aliceProject->id)
            );
    }

    public function test_archived_search_with_no_matches_returns_empty_lists(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->completed()->create(['zoom_meeting_id' => 'archive-test-5']);
        ProjectSubmission::factory()->for($student, 'student')->reviewed()->create();
        TeacherTask::factory()->for($teacher, 'teacher')->completed()->create(['type' => TeacherTaskType::Manual]);
        SupportTicket::factory()->for($teacher, 'teacher')->resolved()->create();

        $this->actingAs($teacher)
            ->get(route('teaching.archive', ['search' => 'no-such-thing-matches-this']))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/TeachingArchive')
                ->count('pastSessions.data', 0)
                ->count('reviewedProjects.data', 0)
                ->count('completedTasks.data', 0)
                ->count('resolvedTickets.data', 0)
            );
    }

    public function test_students_cannot_view_the_archive_page(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('teaching.archive'))
            ->assertForbidden();
    }
}
