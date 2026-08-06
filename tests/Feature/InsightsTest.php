<?php

namespace Tests\Feature;

use App\Enums\FlagSeverity;
use App\Enums\SessionType;
use App\Models\ClassSession;
use App\Models\InsightMetric;
use App\Models\Lesson;
use App\Models\SessionReview;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InsightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_teacher_can_view_the_insights_page(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)
            ->get(route('insights.index'))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Insights')
                ->has('range.from')
                ->has('range.to')
            );
    }

    public function test_students_cannot_view_insights(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('insights.index'))->assertForbidden();
    }

    public function test_guests_are_redirected_away_from_insights(): void
    {
        $this->get(route('insights.index'))->assertRedirect('/login');
    }

    public function test_performance_and_flags_are_computed_from_reviews_in_range(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create();

        $session = ClassSession::factory()
            ->for($teacher, 'teacher')->for($student, 'student')->for($lesson, 'lesson')
            ->completed()
            ->create(['scheduled_at' => now()->subDays(2), 'zoom_meeting_id' => 'insights-test-1']);

        SessionReview::factory()->create([
            'class_session_id' => $session->id,
            'flag_severity' => FlagSeverity::Yellow,
            'feedback_score' => 80,
            'attitude_score' => 80,
            'preparation_score' => 80,
            'curriculum_score' => 80,
            'teaching_score' => 80,
            'setup_score' => 80,
        ]);

        $this->actingAs($teacher)
            ->get(route('insights.index'))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Insights')
                ->where('performance.categories.feedback_score', 80)
                ->where('performance.total_score', 80)
                ->where('flagsBreakdown.yellow', 1)
                ->where('flagsBreakdown.green', 0)
            );
    }

    public function test_sessions_outside_the_requested_range_are_excluded(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create();

        $oldSession = ClassSession::factory()
            ->for($teacher, 'teacher')->for($student, 'student')->for($lesson, 'lesson')
            ->completed()
            ->create(['scheduled_at' => now()->subDays(90), 'zoom_meeting_id' => 'insights-test-2']);

        SessionReview::factory()->create(['class_session_id' => $oldSession->id, 'flag_severity' => FlagSeverity::Red]);

        $this->actingAs($teacher)
            ->get(route('insights.index', ['from' => now()->subDays(30)->toDateString(), 'to' => now()->toDateString()]))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Insights')
                ->where('flagsBreakdown.red', 0)
                ->count('reviewedSessions.data', 0)
            );
    }

    public function test_attendance_splits_on_time_vs_late_by_session_type(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create();
        $scheduledAt = now()->subDays(1);

        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->for($lesson, 'lesson')
            ->completed()
            ->create([
                'type' => SessionType::Online,
                'scheduled_at' => $scheduledAt,
                'teacher_joined_at' => $scheduledAt->copy()->addMinutes(2),
                'zoom_meeting_id' => 'insights-test-3',
            ]);

        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->for($lesson, 'lesson')
            ->completed()
            ->create([
                'type' => SessionType::Online,
                'scheduled_at' => $scheduledAt,
                'teacher_joined_at' => $scheduledAt->copy()->addMinutes(20),
                'zoom_meeting_id' => 'insights-test-4',
            ]);

        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->for($lesson, 'lesson')
            ->completed()
            ->create(['type' => SessionType::Physical, 'scheduled_at' => $scheduledAt, 'teacher_joined_at' => $scheduledAt->copy()->addMinutes(1)]);

        $this->actingAs($teacher)
            ->get(route('insights.index'))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Insights')
                ->where('attendance.join.on_time', 1)
                ->where('attendance.join.late', 1)
                ->where('attendance.check_in.on_time', 1)
                ->where('attendance.check_in.late', 0)
            );
    }

    public function test_insight_metrics_are_summed_into_the_response(): void
    {
        $teacher = User::factory()->teacher()->create();

        InsightMetric::factory()->create([
            'teacher_profile_id' => $teacher->teacherProfile->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'objections' => 2,
            'excuses' => 1,
            'study_level' => 6,
        ]);

        $this->actingAs($teacher)
            ->get(route('insights.index'))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Insights')
                ->where('metrics.objections', 2)
                ->where('metrics.excuses', 1)
                ->where('metrics.study_level', 6)
            );
    }

    public function test_reviewed_sessions_can_be_filtered_by_flag_severity(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $lesson = Lesson::factory()->create();

        $green = ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->for($lesson, 'lesson')
            ->completed()->create(['zoom_meeting_id' => 'insights-test-5']);
        $red = ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->for($lesson, 'lesson')
            ->completed()->create(['zoom_meeting_id' => 'insights-test-6']);

        SessionReview::factory()->create(['class_session_id' => $green->id, 'flag_severity' => FlagSeverity::Green]);
        SessionReview::factory()->create(['class_session_id' => $red->id, 'flag_severity' => FlagSeverity::Red]);

        $this->actingAs($teacher)
            ->get(route('insights.index', ['flag' => 'red']))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Insights')
                ->count('reviewedSessions.data', 1)
                ->where('reviewedSessions.data.0.id', $red->id)
            );
    }

    public function test_reviewed_sessions_can_be_searched_by_student_name(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create(['name' => 'Alice Example']);
        $otherStudent = User::factory()->student()->create(['name' => 'Bob Example']);
        $lesson = Lesson::factory()->create();

        $aliceSession = ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->for($lesson, 'lesson')
            ->completed()->create(['zoom_meeting_id' => 'insights-test-7']);
        $bobSession = ClassSession::factory()->for($teacher, 'teacher')->for($otherStudent, 'student')->for($lesson, 'lesson')
            ->completed()->create(['zoom_meeting_id' => 'insights-test-8']);

        SessionReview::factory()->create(['class_session_id' => $aliceSession->id]);
        SessionReview::factory()->create(['class_session_id' => $bobSession->id]);

        $this->actingAs($teacher)
            ->get(route('insights.index', ['search' => 'Alice']))
            ->assertInertia(fn (Assert $page) => $page->component('teacher/Insights')
                ->count('reviewedSessions.data', 1)
                ->where('reviewedSessions.data.0.id', $aliceSession->id)
            );
    }
}
