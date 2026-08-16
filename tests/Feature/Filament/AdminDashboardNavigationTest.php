<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ClassSessions\ClassSessionResource;
use App\Filament\Resources\FlaggedMessages\FlaggedMessageResource;
use App\Filament\Resources\SessionCreditLedgerEntries\SessionCreditLedgerEntryResource;
use App\Filament\Resources\SessionPackagePurchases\SessionPackagePurchaseResource;
use App\Filament\Resources\SessionPackages\SessionPackageResource;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Tests\TestCase;

class AdminDashboardNavigationTest extends TestCase
{
    /**
     * Safety-critical items should never be buried — this pins the exact
     * priority order requested, so a future resource addition can't
     * silently reorder the sidebar.
     */
    public function test_navigation_groups_are_registered_in_operational_priority_order(): void
    {
        $groups = Filament::getPanel('admin')->getNavigationGroups();

        $labels = collect($groups)
            ->map(fn (NavigationGroup|string $group) => $group instanceof NavigationGroup ? $group->getLabel() : $group)
            ->values()
            ->all();

        $this->assertSame([
            'Safety',
            'Sessions',
            'Curriculum',
            'Payments',
            'Payroll & Requests',
            'Insights',
            'Gamification',
            'Support',
        ], $labels);
    }

    public function test_flagged_messages_sits_immediately_under_safety(): void
    {
        $this->assertSame('Safety', (string) FlaggedMessageResource::getNavigationGroup());
        $this->assertSame(10, FlaggedMessageResource::getNavigationSort());
    }

    public function test_class_session_resource_is_labelled_schedule_not_class_sessions(): void
    {
        $this->assertSame('Schedule', ClassSessionResource::getNavigationLabel());
    }

    public function test_payments_group_resources_are_sorted_as_a_product_transaction_balance_pipeline(): void
    {
        $this->assertLessThan(
            SessionPackagePurchaseResource::getNavigationSort(),
            SessionPackageResource::getNavigationSort(),
        );
        $this->assertLessThan(
            SessionCreditLedgerEntryResource::getNavigationSort(),
            SessionPackagePurchaseResource::getNavigationSort(),
        );
    }

    /**
     * Every resource's global search previously silently returned nothing
     * — Filament requires an explicit title/searchable-attributes
     * declaration, none of the resources had one.
     */
    public function test_every_resource_is_globally_searchable(): void
    {
        $resources = Filament::getPanel('admin')->getResources();

        $this->assertNotEmpty($resources);

        foreach ($resources as $resource) {
            $this->assertNotEmpty(
                $resource::getGloballySearchableAttributes(),
                "{$resource} has no globally searchable attributes."
            );
        }
    }
}
