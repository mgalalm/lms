<?php

namespace Tests\Feature\Filament;

use App\Enums\MessageModerationStatus;
use App\Enums\SessionPackagePurchaseStatus;
use App\Filament\Widgets\OperationalStatsWidget;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\RevenueStatsWidget;
use App\Filament\Widgets\SafetyAlertWidget;
use App\Models\Badge;
use App\Models\ClassSession;
use App\Models\Message;
use App\Models\SessionPackagePurchase;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class AdminDashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_safety_alert_widget_is_hidden_with_no_flagged_messages(): void
    {
        $this->assertFalse(SafetyAlertWidget::canView());
    }

    public function test_admin_dashboard_renders_with_all_widgets_and_a_flagged_message(): void
    {
        Message::withoutEvents(fn () => Message::factory()->create([
            'moderation_status' => MessageModerationStatus::Flagged,
        ]));

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertSeeLivewire(SafetyAlertWidget::class)
            ->assertSeeLivewire(OperationalStatsWidget::class)
            ->assertSeeLivewire(RevenueStatsWidget::class)
            ->assertSeeLivewire(RecentActivityWidget::class);
    }

    public function test_safety_alert_widget_shows_once_a_message_is_flagged(): void
    {
        // Bypass the MessageObserver's auto-moderation job, which would
        // otherwise immediately approve the message and overwrite this
        // status (it runs synchronously in tests, sync queue driver).
        Message::withoutEvents(fn () => Message::factory()->create([
            'moderation_status' => MessageModerationStatus::Flagged,
        ]));

        $this->assertTrue(SafetyAlertWidget::canView());
        $this->assertSame(1, (new SafetyAlertWidget)->getFlaggedCount());
    }

    public function test_safety_alert_widget_ignores_pending_and_approved_messages(): void
    {
        Message::withoutEvents(function () {
            Message::factory()->create(['moderation_status' => MessageModerationStatus::Pending]);
            Message::factory()->create(['moderation_status' => MessageModerationStatus::Approved]);
        });

        $this->assertFalse(SafetyAlertWidget::canView());
    }

    public function test_operational_stats_widget_reports_active_roles_and_session_counts(): void
    {
        // Freeze to a mid-week day so "today" and "another day this week"
        // can't collide — now()->addDays(N) alone is calendar-day-dependent
        // and can cross into next week depending on what day this runs.
        $this->travelTo(now()->startOfWeek()->addDays(2)->setHour(10));

        User::factory()->student()->count(2)->create();
        User::factory()->teacher()->create();

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create([
            'scheduled_at' => today()->addHours(2),
        ]);
        // Same week, different day from "today".
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create([
            'scheduled_at' => now()->startOfWeek()->addHours(1),
        ]);
        // Outside the week entirely — proves the week filter excludes it.
        ClassSession::factory()->for($teacher, 'teacher')->for($student, 'student')->create([
            'scheduled_at' => now()->addMonth(),
        ]);

        $stats = $this->getStats(new OperationalStatsWidget);

        $this->assertSame(3, $stats[0]->getValue()); // Active Students
        $this->assertSame(2, $stats[1]->getValue()); // Active Teachers
        $this->assertSame(1, $stats[2]->getValue()); // Sessions Today
        $this->assertSame(2, $stats[3]->getValue()); // Sessions This Week
    }

    public function test_revenue_stats_widget_only_counts_paid_purchases_from_this_month(): void
    {
        $student = User::factory()->student()->create();

        SessionPackagePurchase::factory()->paid()->for($student)->create([
            'price_cents' => 10000,
            'paid_at' => now(),
        ]);
        SessionPackagePurchase::factory()->paid()->for($student)->create([
            'price_cents' => 5000,
            'paid_at' => now(),
        ]);
        // Not paid — excluded.
        SessionPackagePurchase::factory()->for($student)->create([
            'status' => SessionPackagePurchaseStatus::Pending,
            'price_cents' => 9999,
        ]);
        // Paid, but last month — excluded.
        SessionPackagePurchase::factory()->paid()->for($student)->create([
            'price_cents' => 7500,
            'paid_at' => now()->subMonthNoOverflow(),
        ]);

        $stats = $this->getStats(new RevenueStatsWidget);

        $this->assertSame(2, $stats[0]->getValue());
        $this->assertSame('150.00 EUR', $stats[1]->getValue());
    }

    public function test_recent_activity_widget_merges_and_sorts_mixed_events(): void
    {
        $teacher = User::factory()->teacher()->create();

        $olderStudent = User::factory()->student()->create();
        $olderStudent->studentProfile->forceFill(['created_at' => now()->subDays(3)])->save();

        $newerStudent = User::factory()->student()->create();

        ClassSession::factory()
            ->for($teacher, 'teacher')
            ->for($newerStudent, 'student')
            ->completed()
            ->create(['ended_at' => now()->subHour()]);

        $activity = (new RecentActivityWidget)->getActivity();

        $this->assertNotEmpty($activity);
        $this->assertContains('enrollment', $activity->pluck('type'));
        $this->assertContains('session_completed', $activity->pluck('type'));

        // Sorted newest-first.
        $timestamps = $activity->pluck('timestamp')->all();
        $sorted = collect($timestamps)->sortByDesc(fn ($t) => $t)->values()->all();
        $this->assertEquals($sorted, $timestamps);
    }

    public function test_recent_activity_widget_shows_the_students_decrypted_name_for_badge_awards(): void
    {
        // badgesAwarded() reads users.name via a raw DB::table() join —
        // User.name is encrypted, so this proves the widget resolves the
        // name through the User model (decrypted) rather than returning
        // the raw ciphertext straight off the join.
        $student = User::factory()->student()->create(['name' => 'Jamie Student']);
        $badge = Badge::factory()->create(['name' => 'Time Legend']);
        $student->badges()->attach($badge->id, ['awarded_at' => now()]);

        $activity = (new RecentActivityWidget)->getActivity();
        $badgeEvent = $activity->firstWhere('type', 'badge_awarded');

        $this->assertNotNull($badgeEvent);
        $this->assertSame('Jamie Student earned the "Time Legend" badge', $badgeEvent['description']);
    }

    public function test_recent_activity_widget_includes_flags_linking_to_the_review_page(): void
    {
        Message::withoutEvents(fn () => Message::factory()->create([
            'moderation_status' => MessageModerationStatus::Flagged,
            'moderated_at' => now(),
        ]));

        $activity = (new RecentActivityWidget)->getActivity();
        $flagEvent = $activity->firstWhere('type', 'flag');

        $this->assertNotNull($flagEvent);
        $this->assertNotNull($flagEvent['url']);
    }

    /**
     * @return array<int, Stat>
     */
    private function getStats(object $widget): array
    {
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }
}
