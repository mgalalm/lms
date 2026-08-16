<?php

namespace App\Models;

use App\Enums\SessionCreditReason;
use App\Enums\SessionPackagePurchaseStatus;
use App\Notifications\SessionPackagePurchased;
use Database\Factories\SessionPackagePurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

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

    /**
     * Mark this purchase paid, credit the student's session ledger, and
     * notify them. Idempotent — safe to call even if Stripe redelivers the
     * webhook after an admin has already resolved this purchase manually.
     *
     * Shared by the Stripe webhook listener (CreditSessionPackagePurchase)
     * and the admin "Verify with Stripe" / "Mark as Paid" Filament actions,
     * so both paths credit exactly the same way.
     */
    public function markAsPaid(?string $stripeCheckoutSessionId = null): void
    {
        if ($this->status === SessionPackagePurchaseStatus::Paid) {
            return;
        }

        DB::transaction(function () use ($stripeCheckoutSessionId) {
            $this->update([
                'status' => SessionPackagePurchaseStatus::Paid,
                'stripe_checkout_session_id' => $stripeCheckoutSessionId ?? $this->stripe_checkout_session_id,
                'paid_at' => now(),
            ]);

            $this->user->sessionCreditLedger()->create([
                'credits' => $this->session_count,
                'reason' => SessionCreditReason::Purchase,
                'session_package_purchase_id' => $this->id,
            ]);
        });

        $this->user->notify(new SessionPackagePurchased($this));
    }
}
