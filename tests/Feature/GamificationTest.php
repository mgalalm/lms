<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\PointsLedgerEntry;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_student_can_view_the_achievements_page(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('achievements.index'))
            ->assertInertia(fn (Assert $page) => $page->component('student/Achievements')
                ->where('points', 0)
                ->has('badges')
                ->has('leaderboard')
            );
    }

    public function test_teachers_cannot_view_the_achievements_page(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('achievements.index'))->assertForbidden();
    }

    public function test_guests_are_redirected_away_from_achievements(): void
    {
        $this->get(route('achievements.index'))->assertRedirect('/login');
    }

    public function test_total_points_sums_the_ledger(): void
    {
        $student = User::factory()->student()->create();

        PointsLedgerEntry::factory()->create(['user_id' => $student->id, 'points' => 100]);
        PointsLedgerEntry::factory()->create(['user_id' => $student->id, 'points' => 50]);
        PointsLedgerEntry::factory()->create(['user_id' => $student->id, 'points' => -20]);

        $this->assertSame(130, $student->totalPoints());
    }

    public function test_leaderboard_ranks_users_by_points_descending(): void
    {
        $first = User::factory()->student()->create(['name' => 'Top Student']);
        $second = User::factory()->student()->create(['name' => 'Mid Student']);
        $third = User::factory()->student()->create(['name' => 'Low Student']);

        PointsLedgerEntry::factory()->create(['user_id' => $first->id, 'points' => 300]);
        PointsLedgerEntry::factory()->create(['user_id' => $second->id, 'points' => 200]);
        PointsLedgerEntry::factory()->create(['user_id' => $third->id, 'points' => 100]);

        $this->actingAs($first)
            ->get(route('achievements.index'))
            ->assertInertia(fn (Assert $page) => $page->component('student/Achievements')
                ->where('leaderboard.0.name', 'Top Student')
                ->where('leaderboard.0.rank', 1)
                ->where('leaderboard.1.name', 'Mid Student')
                ->where('leaderboard.2.name', 'Low Student')
            );
    }

    public function test_leaderboard_is_scoped_to_the_students_role(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        PointsLedgerEntry::factory()->create(['user_id' => $student->id, 'points' => 10]);
        PointsLedgerEntry::factory()->create(['user_id' => $teacher->id, 'points' => 999999]);

        $this->actingAs($student)
            ->get(route('achievements.index'))
            ->assertInertia(fn (Assert $page) => $page->component('student/Achievements')
                ->count('leaderboard', 1)
                ->where('leaderboard.0.id', $student->id)
            );
    }

    public function test_earned_badges_appear_on_the_achievements_page(): void
    {
        $student = User::factory()->student()->create();
        $badge = Badge::factory()->create(['name' => 'Quiz Quester']);

        $student->badges()->attach($badge->id, ['awarded_at' => now()]);

        $this->actingAs($student)
            ->get(route('achievements.index'))
            ->assertInertia(fn (Assert $page) => $page->component('student/Achievements')
                ->count('badges', 1)
                ->where('badges.0.name', 'Quiz Quester')
            );
    }

    public function test_dashboard_includes_points_and_leaderboard_for_students(): void
    {
        $student = User::factory()->student()->create();
        PointsLedgerEntry::factory()->create(['user_id' => $student->id, 'points' => 42]);

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('dashboard/Student')
                ->where('points', 42)
                ->has('leaderboard')
                ->has('recentBadges')
            );
    }

    public function test_dashboard_includes_points_and_leaderboard_for_teachers(): void
    {
        $teacher = User::factory()->teacher()->create();
        PointsLedgerEntry::factory()->create(['user_id' => $teacher->id, 'points' => 500]);

        $this->actingAs($teacher)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->component('dashboard/Teacher')
                ->where('points', 500)
                ->has('leaderboard')
            );
    }
}
