<?php

namespace App\Services\Stripe;

use App\Models\SessionPackage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Laravel\Cashier\Cashier;
use Stripe\Coupon;
use Stripe\PromotionCode;

class PromotionCodeClient
{
    /**
     * List promotion codes, newest first. Stripe is the only source of
     * truth for this data — nothing is duplicated locally.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function list(): Collection
    {
        $codes = Cashier::stripe()->promotionCodes->all([
            'limit' => 100,
            'expand' => ['data.coupon'],
        ]);

        return collect($codes->data)
            ->map(fn (PromotionCode $code) => $this->present($code))
            ->keyBy('id');
    }

    /**
     * Create a Coupon (the discount definition) and a PromotionCode (the
     * human-typeable code customers enter at checkout) referencing it.
     *
     * @param  array{code: string, discount_type: 'percentage'|'fixed', discount_value: int, session_package_ids: array<int, int>, expires_at: ?Carbon, max_redemptions: ?int}  $data
     */
    public function create(array $data): void
    {
        $productIds = SessionPackage::query()
            ->whereIn('id', $data['session_package_ids'])
            ->get()
            ->map(fn (SessionPackage $package) => $this->resolveProductId($package))
            ->all();

        $coupon = Cashier::stripe()->coupons->create(array_filter([
            'percent_off' => $data['discount_type'] === 'percentage' ? $data['discount_value'] : null,
            'amount_off' => $data['discount_type'] === 'fixed' ? $data['discount_value'] : null,
            'currency' => $data['discount_type'] === 'fixed' ? config('cashier.currency', 'eur') : null,
            'duration' => 'once',
            'applies_to' => $productIds !== [] ? ['products' => $productIds] : null,
        ], fn ($value) => $value !== null));

        Cashier::stripe()->promotionCodes->create(array_filter([
            'coupon' => $coupon->id,
            'code' => $data['code'],
            'expires_at' => $data['expires_at']?->timestamp,
            'max_redemptions' => $data['max_redemptions'],
        ], fn ($value) => $value !== null));
    }

    /**
     * Promotion codes can't be deleted in Stripe, only deactivated.
     */
    public function deactivate(string $id): void
    {
        Cashier::stripe()->promotionCodes->update($id, ['active' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(PromotionCode $code): array
    {
        return [
            'id' => $code->id,
            'code' => $code->code,
            'active' => $code->active,
            'discount' => $this->describeDiscount($code->coupon),
            'times_redeemed' => $code->times_redeemed,
            'max_redemptions' => $code->max_redemptions,
            'expires_at' => $code->expires_at ? Carbon::createFromTimestamp($code->expires_at) : null,
            'created' => Carbon::createFromTimestamp($code->created),
        ];
    }

    private function describeDiscount(Coupon $coupon): string
    {
        if ($coupon->percent_off) {
            return rtrim(rtrim(number_format((float) $coupon->percent_off, 2), '0'), '.').'% off';
        }

        return number_format($coupon->amount_off / 100, 2).' '.strtoupper($coupon->currency).' off';
    }

    private function resolveProductId(SessionPackage $package): string
    {
        $price = Cashier::stripe()->prices->retrieve($package->resolveStripePriceId());

        return is_string($price->product) ? $price->product : $price->product->id;
    }
}
