<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\PromoCodes;
use App\Models\SessionPackage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

class PromoCodesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_see_a_list_of_promotion_codes(): void
    {
        $this->fakeStripe();

        $admin = $this->admin();
        $this->actingAs($admin);

        $html = Livewire::test(PromoCodes::class)->call('loadTable')->html();

        $this->assertStringContainsString('SUMMER25', $html);
        $this->assertStringContainsString('25% off', $html);
    }

    public function test_admin_can_create_a_promotion_code_scoped_to_a_package(): void
    {
        $captured = [];
        $this->fakeStripe($captured);

        $admin = $this->admin();
        $this->actingAs($admin);

        $package = SessionPackage::factory()->create(['stripe_lookup_key' => 'starter_pack']);

        Livewire::test(PromoCodes::class)
            ->callAction('create', data: [
                'code' => 'WELCOME10',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'session_package_ids' => [$package->id],
                'expires_at' => null,
                'max_redemptions' => 50,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(10, $captured['coupon']['percent_off']);
        $this->assertSame(['prod_test123'], $captured['coupon']['applies_to']['products']);
        $this->assertSame('WELCOME10', $captured['promotion_code']['code']);
        $this->assertSame('coupon_test123', $captured['promotion_code']['promotion']['coupon']);
        $this->assertSame(50, $captured['promotion_code']['max_redemptions']);
    }

    public function test_admin_can_deactivate_a_promotion_code(): void
    {
        $captured = [];
        $this->fakeStripe($captured);

        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(PromoCodes::class)
            ->call('loadTable')
            ->callTableAction('deactivate', 'promo_test123');

        $this->assertSame('false', $captured['deactivate']['active']);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function fakeStripe(array &$captured = []): void
    {
        $client = new class($captured) implements ClientInterface
        {
            public function __construct(private mixed &$captured) {}

            public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                if (str_contains($absUrl, '/v1/prices/price_resolved_fake123')) {
                    return [json_encode([
                        'id' => 'price_resolved_fake123',
                        'object' => 'price',
                        'product' => 'prod_test123',
                    ]), 200, []];
                }

                if (str_contains($absUrl, '/v1/prices')) {
                    return [json_encode([
                        'object' => 'list',
                        'data' => [
                            ['id' => 'price_resolved_fake123', 'object' => 'price'],
                        ],
                    ]), 200, []];
                }

                if ($method === 'post' && str_contains($absUrl, '/v1/coupons')) {
                    $this->captured['coupon'] = $params;

                    return [json_encode([
                        'id' => 'coupon_test123',
                        'object' => 'coupon',
                        'percent_off' => $params['percent_off'] ?? null,
                        'amount_off' => $params['amount_off'] ?? null,
                        'currency' => $params['currency'] ?? null,
                    ]), 200, []];
                }

                if ($method === 'post' && str_contains($absUrl, '/v1/promotion_codes/')) {
                    $this->captured['deactivate'] = $params;

                    return [json_encode([
                        'id' => 'promo_test123',
                        'object' => 'promotion_code',
                        'code' => 'SUMMER25',
                        'active' => false,
                        'times_redeemed' => 3,
                        'max_redemptions' => 10,
                        'expires_at' => null,
                        'created' => 1700000000,
                        'promotion' => [
                            'type' => 'coupon',
                            'coupon' => [
                                'id' => 'coupon_test123',
                                'object' => 'coupon',
                                'percent_off' => 25,
                                'amount_off' => null,
                                'currency' => null,
                            ],
                        ],
                    ]), 200, []];
                }

                if ($method === 'post' && str_contains($absUrl, '/v1/promotion_codes')) {
                    $this->captured['promotion_code'] = $params;

                    return [json_encode([
                        'id' => 'promo_new123',
                        'object' => 'promotion_code',
                        'code' => $params['code'],
                        'active' => true,
                        'times_redeemed' => 0,
                        'max_redemptions' => $params['max_redemptions'] ?? null,
                        'expires_at' => $params['expires_at'] ?? null,
                        'created' => 1700000000,
                        'promotion' => [
                            'type' => 'coupon',
                            'coupon' => [
                                'id' => 'coupon_test123',
                                'object' => 'coupon',
                                'percent_off' => 10,
                                'amount_off' => null,
                                'currency' => null,
                            ],
                        ],
                    ]), 200, []];
                }

                if (str_contains($absUrl, '/v1/promotion_codes')) {
                    return [json_encode([
                        'object' => 'list',
                        'data' => [[
                            'id' => 'promo_test123',
                            'object' => 'promotion_code',
                            'code' => 'SUMMER25',
                            'active' => true,
                            'times_redeemed' => 3,
                            'max_redemptions' => 10,
                            'expires_at' => null,
                            'created' => 1700000000,
                            'promotion' => [
                                'type' => 'coupon',
                                'coupon' => [
                                    'id' => 'coupon_test123',
                                    'object' => 'coupon',
                                    'percent_off' => 25,
                                    'amount_off' => null,
                                    'currency' => null,
                                ],
                            ],
                        ]],
                    ]), 200, []];
                }

                return [json_encode(['id' => 'unknown', 'object' => 'unknown']), 200, []];
            }
        };

        ApiRequestor::setHttpClient($client);

        $this->beforeApplicationDestroyed(fn () => ApiRequestor::setHttpClient(null));
    }
}
