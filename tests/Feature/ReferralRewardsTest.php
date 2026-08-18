<?php

namespace Tests\Feature;

use App\Enums\SessionCreditReason;
use App\Models\ReferralCode;
use App\Models\User;
use App\Services\Referrals\ReferralRedemptionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralRewardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_redeeming_a_valid_code_credits_both_the_referrer_and_the_referee(): void
    {
        $referrer = User::factory()->student()->create();
        $referee = User::factory()->student()->create();
        $referralCode = ReferralCode::factory()->for($referrer, 'referrer')->create([
            'reward_sessions_referrer' => 2,
            'reward_sessions_referee' => 3,
        ]);

        $redeemed = app(ReferralRedemptionService::class)->redeem($referee, $referralCode->code);

        $this->assertTrue($redeemed);
        $this->assertSame(3, $referee->sessionCreditsBalance());
        $this->assertSame(2, $referrer->sessionCreditsBalance());

        $this->assertDatabaseHas('session_credit_ledger_entries', [
            'user_id' => $referee->id,
            'credits' => 3,
            'reason' => SessionCreditReason::Referral->value,
            'referral_code_id' => $referralCode->id,
        ]);
        $this->assertDatabaseHas('session_credit_ledger_entries', [
            'user_id' => $referrer->id,
            'credits' => 2,
            'reason' => SessionCreditReason::Referral->value,
            'referral_code_id' => $referralCode->id,
        ]);
    }

    public function test_redeeming_a_limited_code_decrements_uses_remaining(): void
    {
        $referrer = User::factory()->student()->create();
        $referee = User::factory()->student()->create();
        $referralCode = ReferralCode::factory()->for($referrer, 'referrer')->create(['uses_remaining' => 3]);

        app(ReferralRedemptionService::class)->redeem($referee, $referralCode->code);

        $this->assertSame(2, $referralCode->fresh()->uses_remaining);
    }

    public function test_redeeming_an_unlimited_code_does_not_touch_uses_remaining(): void
    {
        $referrer = User::factory()->student()->create();
        $referee = User::factory()->student()->create();
        $referralCode = ReferralCode::factory()->for($referrer, 'referrer')->create(['uses_remaining' => null]);

        app(ReferralRedemptionService::class)->redeem($referee, $referralCode->code);

        $this->assertNull($referralCode->fresh()->uses_remaining);
    }

    public function test_an_expired_code_is_rejected_and_grants_no_credits(): void
    {
        $referrer = User::factory()->student()->create();
        $referee = User::factory()->student()->create();
        $referralCode = ReferralCode::factory()->for($referrer, 'referrer')->expired()->create();

        $redeemed = app(ReferralRedemptionService::class)->redeem($referee, $referralCode->code);

        $this->assertFalse($redeemed);
        $this->assertSame(0, $referee->sessionCreditsBalance());
        $this->assertSame(0, $referrer->sessionCreditsBalance());
        $this->assertDatabaseCount('session_credit_ledger_entries', 0);
    }

    public function test_an_exhausted_code_is_rejected_and_grants_no_credits(): void
    {
        $referrer = User::factory()->student()->create();
        $referee = User::factory()->student()->create();
        $referralCode = ReferralCode::factory()->for($referrer, 'referrer')->exhausted()->create();

        $redeemed = app(ReferralRedemptionService::class)->redeem($referee, $referralCode->code);

        $this->assertFalse($redeemed);
        $this->assertSame(0, $referee->sessionCreditsBalance());
        $this->assertSame(0, $referrer->sessionCreditsBalance());
    }

    public function test_a_user_cannot_redeem_their_own_referral_code(): void
    {
        $referrer = User::factory()->student()->create();
        $referralCode = ReferralCode::factory()->for($referrer, 'referrer')->create();

        $redeemed = app(ReferralRedemptionService::class)->redeem($referrer, $referralCode->code);

        $this->assertFalse($redeemed);
        $this->assertSame(0, $referrer->sessionCreditsBalance());
    }

    public function test_a_nonexistent_code_is_rejected(): void
    {
        $referee = User::factory()->student()->create();

        $redeemed = app(ReferralRedemptionService::class)->redeem($referee, 'NOTACODE');

        $this->assertFalse($redeemed);
    }

    public function test_a_user_who_already_redeemed_a_code_cannot_redeem_another(): void
    {
        $referrer = User::factory()->student()->create();
        $referee = User::factory()->student()->create();
        $firstCode = ReferralCode::factory()->for($referrer, 'referrer')->create();
        $secondCode = ReferralCode::factory()->create();

        $referrals = app(ReferralRedemptionService::class);
        $this->assertTrue($referrals->redeem($referee, $firstCode->code));
        $this->assertFalse($referrals->redeem($referee, $secondCode->code));

        $this->assertSame(1, $referee->sessionCreditLedger()->where('reason', SessionCreditReason::Referral)->count());
    }

    public function test_for_user_auto_creates_a_unique_code_on_first_request_and_reuses_it_after(): void
    {
        $user = User::factory()->student()->create();

        $first = ReferralCode::forUser($user);
        $second = ReferralCode::forUser($user);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('referral_codes', 1);
    }

    public function test_registering_with_a_valid_referral_code_credits_both_parties(): void
    {
        $referrer = User::factory()->student()->create();
        $referralCode = ReferralCode::factory()->for($referrer, 'referrer')->create([
            'reward_sessions_referrer' => 2,
            'reward_sessions_referee' => 3,
        ]);

        $this->post(route('register'), [
            'name' => 'New Student',
            'email' => 'new-student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'parent_email' => 'parent@example.com',
            'consent' => true,
            'referral_code' => $referralCode->code,
        ]);

        $newStudent = User::where('email', 'new-student@example.com')->firstOrFail();

        $this->assertSame(3, $newStudent->sessionCreditsBalance());
        $this->assertSame(2, $referrer->sessionCreditsBalance());
    }

    public function test_registering_with_an_invalid_referral_code_still_succeeds_without_crediting_anyone(): void
    {
        $this->post(route('register'), [
            'name' => 'New Student',
            'email' => 'new-student@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'parent_email' => 'parent@example.com',
            'consent' => true,
            'referral_code' => 'BOGUSCODE',
        ])->assertRedirect(route('dashboard'));

        $newStudent = User::where('email', 'new-student@example.com')->firstOrFail();

        $this->assertSame(0, $newStudent->sessionCreditsBalance());
    }

    public function test_the_validation_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('referral-codes.validate'), ['code' => 'ANYCODE'])
                ->assertOk();
        }

        $this->postJson(route('referral-codes.validate'), ['code' => 'ANYCODE'])
            ->assertStatus(429);
    }
}
