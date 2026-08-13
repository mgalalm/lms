<?php

namespace App\Models;

use App\Enums\SessionPackagePurchaseStatus;
use Database\Factories\SessionPackagePurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SessionPackagePurchase extends Model
{
    /** @use HasFactory<SessionPackagePurchaseFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'session_package_id',
        'status',
        'session_count',
        'price_cents',
        'currency',
        'stripe_checkout_session_id',
        'paid_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SessionPackagePurchaseStatus::class,
            'session_count' => 'integer',
            'price_cents' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the student who made this purchase.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the package that was purchased.
     */
    public function sessionPackage(): BelongsTo
    {
        return $this->belongsTo(SessionPackage::class);
    }

    /**
     * Get the ledger entry crediting this purchase, once paid.
     */
    public function ledgerEntry(): HasOne
    {
        return $this->hasOne(SessionCreditLedgerEntry::class);
    }
}
