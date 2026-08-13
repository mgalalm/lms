<?php

namespace App\Models;

use Database\Factories\SessionPackageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'stripe_price_id',
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
     * Price ID configured), for the student-facing storefront.
     */
    public function scopePurchasable(Builder $query): Builder
    {
        return $query->where('active', true)->whereNotNull('stripe_price_id');
    }
}
