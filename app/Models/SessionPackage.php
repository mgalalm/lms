<?php

namespace App\Models;

use Database\Factories\SessionPackageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Cashier;
use RuntimeException;

class SessionPackage extends Model
{
    /** @use HasFactory<SessionPackageFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'session_count',
        'price_cents',
        'currency',
        'stripe_lookup_key',
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'session_count' => 'integer',
            'price_cents' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the purchases made of this package.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(SessionPackagePurchase::class);
    }

    /**
     * Scope packages that are active and ready to be sold (have a Stripe
     * lookup key configured), for the student-facing storefront.
     */
    public function scopePurchasable(Builder $query): Builder
    {
        return $query->where('active', true)->whereNotNull('stripe_lookup_key');
    }

    /**
     * Resolve this package's Stripe lookup key to its current live Price
     * ID. Looking up by key (rather than storing the Price ID directly)
     * means the underlying Price can be rotated in Stripe without needing
     * a corresponding update here. Cached briefly since this hits Stripe's
     * API on every checkout attempt otherwise.
     */
    public function resolveStripePriceId(): string
    {
        return Cache::remember(
            "stripe-price-id:{$this->stripe_lookup_key}",
            now()->addHour(),
            function () {
                $prices = Cashier::stripe()->prices->all([
                    'lookup_keys' => [$this->stripe_lookup_key],
                    'limit' => 1,
                ]);

                if (empty($prices->data)) {
                    throw new RuntimeException("No active Stripe price found for lookup key [{$this->stripe_lookup_key}].");
                }

                return $prices->data[0]->id;
            }
        );
    }
}
