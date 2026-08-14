<?php

namespace Tests\Feature;

use App\Enums\SessionPackagePurchaseStatus;
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

    private function fakeStripeHttpClient(): void
    {
        $client = new class implements ClientInterface
        {
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
