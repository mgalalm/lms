<?php

namespace Tests\Feature\Filament;

use App\Enums\SessionPackagePurchaseStatus;
use App\Filament\Resources\SessionPackagePurchases\Pages\ListSessionPackagePurchases;
use App\Filament\Resources\SessionPackagePurchases\Pages\ViewSessionPackagePurchase;
use App\Models\SessionPackagePurchase;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SessionPackagePurchasesAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * Regression test: the status column's badge color closure previously
     * type-hinted `string $state`, but Filament passes the actual
     * SessionPackagePurchaseStatus enum instance (the column is enum-cast),
     * which threw a TypeError and 500'd the whole page for any admin with
     * at least one purchase to display.
     */
    public function test_admin_can_view_the_purchases_list_with_every_status_present(): void
    {
        $admin = $this->admin();

        foreach (SessionPackagePurchaseStatus::cases() as $status) {
            SessionPackagePurchase::factory()->create(['status' => $status]);
        }

        $this->actingAs($admin);

        Livewire::test(ListSessionPackagePurchases::class)
            ->assertOk()
            ->assertCanSeeTableRecords(SessionPackagePurchase::all());
    }

    public function test_admin_can_view_a_single_purchase(): void
    {
        $admin = $this->admin();
        $purchase = SessionPackagePurchase::factory()->paid()->create();

        $this->actingAs($admin);

        Livewire::test(ViewSessionPackagePurchase::class, ['record' => $purchase->getRouteKey()])
            ->assertOk();
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
