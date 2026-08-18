<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ReferralCodes\Pages\EditReferralCode;
use App\Filament\Resources\ReferralCodes\Pages\ListReferralCodes;
use App\Filament\Resources\ReferralCodes\ReferralCodeResource;
use App\Filament\Resources\ReferralCodes\RelationManagers\RefereeCreditsRelationManager;
use App\Models\ReferralCode;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReferralCodesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_view_the_referral_codes_list_with_a_redemption_count(): void
    {
        $admin = $this->admin();
        [$referralCode] = $this->createRedeemedReferralCode();

        $this->actingAs($admin);

        Livewire::test(ListReferralCodes::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$referralCode])
            ->assertSee($referralCode->code);
    }

    public function test_admin_can_override_uses_remaining_and_expiry_on_a_referral_code(): void
    {
        $admin = $this->admin();
        $referralCode = ReferralCode::factory()->create(['uses_remaining' => null]);

        $this->actingAs($admin);

        Livewire::test(EditReferralCode::class, ['record' => $referralCode->getRouteKey()])
            ->fillForm(['uses_remaining' => 5])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(5, $referralCode->fresh()->uses_remaining);
    }

    public function test_referral_codes_cannot_be_created_or_deleted_from_the_admin_panel(): void
    {
        // Codes are only ever auto-created via ReferralCode::forUser(); the
        // resource is a usage report with a mutable-fields override, not a
        // place to fabricate codes or erase redemption history.
        $this->assertFalse(ReferralCodeResource::canCreate());
    }

    public function test_relation_manager_lists_who_redeemed_the_code(): void
    {
        $admin = $this->admin();
        [$referralCode, $referee] = $this->createRedeemedReferralCode();

        $this->actingAs($admin);

        Livewire::test(RefereeCreditsRelationManager::class, [
            'ownerRecord' => $referralCode,
            'pageClass' => EditReferralCode::class,
        ])
            ->assertOk()
            ->assertSee($referee->email);
    }

    /**
     * @return array{0: ReferralCode, 1: User, 2: User}
     */
    private function createRedeemedReferralCode(): array
    {
        $referrer = User::factory()->student()->create();
        $referee = User::factory()->student()->create();

        $referralCode = ReferralCode::factory()->for($referrer, 'referrer')->create();

        $referee->sessionCreditLedger()->create([
            'credits' => $referralCode->reward_sessions_referee,
            'reason' => 'referral',
            'referral_code_id' => $referralCode->id,
        ]);
        $referrer->sessionCreditLedger()->create([
            'credits' => $referralCode->reward_sessions_referrer,
            'reason' => 'referral',
            'referral_code_id' => $referralCode->id,
        ]);

        return [$referralCode, $referee, $referrer];
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
