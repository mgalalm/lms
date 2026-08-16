<?php

namespace Tests\Feature\Filament;

use App\Enums\SessionPackagePurchaseStatus;
use App\Filament\Resources\SessionPackagePurchases\Pages\ListSessionPackagePurchases;
use App\Filament\Resources\SessionPackagePurchases\Pages\ViewSessionPackagePurchase;
use App\Filament\Resources\SessionPackagePurchases\SessionPackagePurchaseActions;
use App\Models\SessionPackagePurchase;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
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

    public function test_verify_with_stripe_credits_a_purchase_that_stripe_confirms_paid(): void
    {
        $this->fakeStripeCheckoutRetrieve('paid');

        $purchase = SessionPackagePurchase::factory()->create([
            'status' => SessionPackagePurchaseStatus::Pending,
            'stripe_checkout_session_id' => 'cs_test_verify123',
            'session_count' => 10,
        ]);

        SessionPackagePurchaseActions::verifyWithStripe($purchase);

        $purchase->refresh();
        $this->assertSame(SessionPackagePurchaseStatus::Paid, $purchase->status);
        $this->assertSame(10, $purchase->user->sessionCreditsBalance());
    }

    public function test_verify_with_stripe_does_not_credit_a_purchase_stripe_still_shows_unpaid(): void
    {
        $this->fakeStripeCheckoutRetrieve('unpaid');

        $purchase = SessionPackagePurchase::factory()->create([
            'status' => SessionPackagePurchaseStatus::Pending,
            'stripe_checkout_session_id' => 'cs_test_verify456',
        ]);

        SessionPackagePurchaseActions::verifyWithStripe($purchase);

        $purchase->refresh();
        $this->assertSame(SessionPackagePurchaseStatus::Pending, $purchase->status);
        $this->assertSame(0, $purchase->user->sessionCreditsBalance());
    }

    public function test_verify_with_stripe_does_nothing_without_a_checkout_session_on_record(): void
    {
        $purchase = SessionPackagePurchase::factory()->create([
            'status' => SessionPackagePurchaseStatus::Pending,
            'stripe_checkout_session_id' => null,
        ]);

        SessionPackagePurchaseActions::verifyWithStripe($purchase);

        $this->assertSame(SessionPackagePurchaseStatus::Pending, $purchase->fresh()->status);
    }

    public function test_admin_can_mark_a_purchase_as_paid_manually_without_stripe(): void
    {
        $admin = $this->admin();
        $purchase = SessionPackagePurchase::factory()->create([
            'status' => SessionPackagePurchaseStatus::Pending,
            'stripe_checkout_session_id' => null,
            'session_count' => 5,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListSessionPackagePurchases::class)
            ->callTableAction('markAsPaid', $purchase);

        $purchase->refresh();
        $this->assertSame(SessionPackagePurchaseStatus::Paid, $purchase->status);
        $this->assertSame(5, $purchase->user->sessionCreditsBalance());
    }

    public function test_marking_an_already_paid_purchase_as_paid_again_does_not_double_credit(): void
    {
        $purchase = SessionPackagePurchase::factory()->paid()->create(['session_count' => 5]);
        $purchase->user->sessionCreditLedger()->create(['credits' => 5, 'reason' => 'purchase']);

        SessionPackagePurchaseActions::markAsPaidManually($purchase);

        $this->assertSame(5, $purchase->user->sessionCreditsBalance());
    }

    private function fakeStripeCheckoutRetrieve(string $paymentStatus): void
    {
        $client = new class($paymentStatus) implements ClientInterface
        {
            public function __construct(private string $paymentStatus) {}

            public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                return [json_encode([
                    'id' => 'cs_test_verify123',
                    'object' => 'checkout.session',
                    'payment_status' => $this->paymentStatus,
                ]), 200, []];
            }
        };

        ApiRequestor::setHttpClient($client);

        $this->beforeApplicationDestroyed(fn () => ApiRequestor::setHttpClient(null));
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
