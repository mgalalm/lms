<?php

namespace Tests\Feature;

use App\Enums\SessionCreditReason;
use App\Enums\SessionPackagePurchaseStatus;
use App\Models\ReferralCode;
use App\Models\SessionCreditLedgerEntry;
use App\Models\SessionPackage;
use App\Models\SessionPackagePurchase;
use App\Models\User;
use App\Notifications\SessionPackagePurchased;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Cashier\Events\WebhookReceived;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

class SessionPackagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_index_shows_balance_purchasable_packages_and_purchase_history(): void
    {
        $student = User::factory()->student()->create();
        $student->sessionCreditLedger()->create(['credits' => 3, 'reason' => 'purchase']);

        $purchasable = SessionPackage::factory()->create(['name' => '10-Session Package']);
        SessionPackage::factory()->create(['active' => false, 'name' => 'Hidden Package']);
        SessionPackage::factory()->create(['stripe_lookup_key' => null, 'name' => 'Unpriced Package']);

        SessionPackagePurchase::factory()->paid()->for($student)->for($purchasable, 'sessionPackage')->create();

        $this->actingAs($student)
            ->get(route('session-packages.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('student/SessionPackages')
                ->where('balance', 3)
                ->has('packages', 1)
                ->has('purchases', 1)
            );
    }

    public function test_checkout_creates_a_pending_purchase_and_redirects_to_stripe(): void
    {
        $this->fakeStripeHttpClient();

        $student = User::factory()->student()->create();
        $package = SessionPackage::factory()->create([
            'stripe_lookup_key' => 'starter_pack',
            'session_count' => 10,
            'price_cents' => 15000,
        ]);

        $response = $this->actingAs($student)->get(route('session-packages.checkout', $package));

        $response->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_fake123');

        $this->assertDatabaseHas('session_package_purchases', [
            'user_id' => $student->id,
            'session_package_id' => $package->id,
            'status' => SessionPackagePurchaseStatus::Pending->value,
            'session_count' => 10,
            'price_cents' => 15000,
            'stripe_checkout_session_id' => 'cs_test_fake123',
        ]);
    }

    public function test_checkout_allows_stripe_promotion_codes(): void
    {
        $capturedParams = null;
        $this->fakeStripeHttpClient($capturedParams);

        $student = User::factory()->student()->create();
        $package = SessionPackage::factory()->create(['stripe_lookup_key' => 'starter_pack']);

        $this->actingAs($student)->get(route('session-packages.checkout', $package));

        $this->assertNotNull($capturedParams);
        $this->assertSame('true', $capturedParams['allow_promotion_codes'] ?? null);
    }

    public function test_checkout_stores_the_submitted_referral_code_on_the_pending_purchase(): void
    {
        $this->fakeStripeHttpClient();

        $student = User::factory()->student()->create();
        $package = SessionPackage::factory()->create(['stripe_lookup_key' => 'starter_pack']);

        $this->actingAs($student)
            ->get(route('session-packages.checkout', ['sessionPackage' => $package, 'referral_code' => 'abc123']));

        $this->assertDatabaseHas('session_package_purchases', [
            'user_id' => $student->id,
            'referral_code' => 'ABC123',
        ]);
    }

    public function test_checkout_for_a_package_without_a_stripe_lookup_key_is_not_found(): void
    {
        $student = User::factory()->student()->create();
        $package = SessionPackage::factory()->create(['stripe_lookup_key' => null]);

        $this->actingAs($student)
            ->get(route('session-packages.checkout', $package))
            ->assertNotFound();

        $this->assertDatabaseCount('session_package_purchases', 0);
    }

    public function test_cancelling_checkout_marks_the_pending_purchase_cancelled(): void
    {
        $student = User::factory()->student()->create();
        $purchase = SessionPackagePurchase::factory()->for($student)->create([
            'status' => SessionPackagePurchaseStatus::Pending,
        ]);

        $this->actingAs($student)
            ->get(route('session-packages.checkout.cancel', $purchase))
            ->assertRedirect(route('session-packages.index'));

        $this->assertSame(SessionPackagePurchaseStatus::Cancelled, $purchase->fresh()->status);
    }

    public function test_webhook_credits_the_ledger_and_notifies_the_student_on_paid_checkout(): void
    {
        Notification::fake();

        $student = User::factory()->student()->create();
        $package = SessionPackage::factory()->create(['session_count' => 10]);
        $purchase = SessionPackagePurchase::factory()->for($student)->for($package, 'sessionPackage')->create([
            'status' => SessionPackagePurchaseStatus::Pending,
            'session_count' => 10,
        ]);

        Event::dispatch(new WebhookReceived($this->checkoutCompletedPayload($purchase->id)));

        $this->assertSame(SessionPackagePurchaseStatus::Paid, $purchase->fresh()->status);
        $this->assertNotNull($purchase->fresh()->paid_at);
        $this->assertSame(10, $student->sessionCreditsBalance());

        $this->assertDatabaseHas('session_credit_ledger_entries', [
            'user_id' => $student->id,
            'credits' => 10,
            'session_package_purchase_id' => $purchase->id,
        ]);

        Notification::assertSentTo(
            $student,
            SessionPackagePurchased::class,
            fn (SessionPackagePurchased $notification) => $notification->purchase->is($purchase)
        );
    }

    public function test_webhook_ignores_unrelated_event_types(): void
    {
        $student = User::factory()->student()->create();
        $purchase = SessionPackagePurchase::factory()->for($student)->create([
            'status' => SessionPackagePurchaseStatus::Pending,
        ]);

        Event::dispatch(new WebhookReceived([
            'type' => 'customer.updated',
            'data' => ['object' => []],
        ]));

        $this->assertSame(SessionPackagePurchaseStatus::Pending, $purchase->fresh()->status);
        $this->assertSame(0, SessionCreditLedgerEntry::count());
    }

    public function test_webhook_does_not_credit_an_unpaid_checkout_session(): void
    {
        $student = User::factory()->student()->create();
        $purchase = SessionPackagePurchase::factory()->for($student)->create([
            'status' => SessionPackagePurchaseStatus::Pending,
        ]);

        Event::dispatch(new WebhookReceived([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_unpaid',
                'payment_status' => 'unpaid',
                'metadata' => ['session_package_purchase_id' => $purchase->id],
            ]],
        ]));

        $this->assertSame(SessionPackagePurchaseStatus::Pending, $purchase->fresh()->status);
        $this->assertSame(0, $student->sessionCreditsBalance());
    }

    public function test_webhook_does_not_double_credit_an_already_paid_purchase(): void
    {
        $student = User::factory()->student()->create();
        $purchase = SessionPackagePurchase::factory()->paid()->for($student)->create([
            'session_count' => 10,
        ]);
        $student->sessionCreditLedger()->create([
            'credits' => 10,
            'reason' => 'purchase',
            'session_package_purchase_id' => $purchase->id,
        ]);

        Event::dispatch(new WebhookReceived($this->checkoutCompletedPayload($purchase->id)));

        $this->assertSame(10, $student->sessionCreditsBalance());
        $this->assertSame(1, SessionCreditLedgerEntry::count());
    }

    public function test_webhook_redeems_a_referral_code_submitted_at_checkout_and_credits_both_parties(): void
    {
        $referrer = User::factory()->student()->create();
        $referralCode = ReferralCode::factory()->for($referrer, 'referrer')->create([
            'reward_sessions_referrer' => 2,
            'reward_sessions_referee' => 3,
        ]);

        $student = User::factory()->student()->create();
        $package = SessionPackage::factory()->create(['session_count' => 10]);
        $purchase = SessionPackagePurchase::factory()->for($student)->for($package, 'sessionPackage')->create([
            'status' => SessionPackagePurchaseStatus::Pending,
            'session_count' => 10,
            'referral_code' => $referralCode->code,
        ]);

        Event::dispatch(new WebhookReceived($this->checkoutCompletedPayload($purchase->id)));

        $this->assertSame(13, $student->sessionCreditsBalance());
        $this->assertSame(2, $referrer->sessionCreditsBalance());
        $this->assertNull($referralCode->fresh()->uses_remaining);

        $this->assertDatabaseHas('session_credit_ledger_entries', [
            'user_id' => $student->id,
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

    /**
     * @return array<string, mixed>
     */
    private function checkoutCompletedPayload(int $purchaseId): array
    {
        return [
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_paid123',
                'payment_status' => 'paid',
                'metadata' => ['session_package_purchase_id' => (string) $purchaseId],
            ]],
        ];
    }

    private function fakeStripeHttpClient(?array &$capturedCheckoutSessionParams = null): void
    {
        $client = new class($capturedCheckoutSessionParams) implements ClientInterface
        {
            public function __construct(private mixed &$capturedCheckoutSessionParams) {}

            public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                if (str_contains($absUrl, '/v1/customers')) {
                    return [json_encode(['id' => 'cus_test_fake123', 'object' => 'customer']), 200, []];
                }

                if (str_contains($absUrl, '/v1/prices')) {
                    return [json_encode([
                        'object' => 'list',
                        'data' => [
                            ['id' => 'price_resolved_fake123', 'object' => 'price'],
                        ],
                    ]), 200, []];
                }

                if (str_contains($absUrl, '/v1/checkout/sessions')) {
                    $this->capturedCheckoutSessionParams = $params;

                    return [json_encode([
                        'id' => 'cs_test_fake123',
                        'object' => 'checkout.session',
                        'url' => 'https://checkout.stripe.com/c/pay/cs_test_fake123',
                    ]), 200, []];
                }

                return [json_encode(['id' => 'unknown', 'object' => 'unknown']), 200, []];
            }
        };

        ApiRequestor::setHttpClient($client);

        $this->beforeApplicationDestroyed(fn () => ApiRequestor::setHttpClient(null));
    }
}
