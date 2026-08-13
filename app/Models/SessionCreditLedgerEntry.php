<?php

namespace App\Models;

use App\Enums\SessionCreditReason;
use Database\Factories\SessionCreditLedgerEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionCreditLedgerEntry extends Model
{
    /** @use HasFactory<SessionCreditLedgerEntryFactory> */
    use HasFactory;

    protected $table = 'session_credit_ledger_entries';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'credits',
        'reason',
        'session_package_purchase_id',
        'class_session_id',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'reason' => SessionCreditReason::class,
        ];
    }

    /**
     * Get the user this ledger entry belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the purchase that generated this entry, if it's a credit from a purchase.
     */
    public function sessionPackagePurchase(): BelongsTo
    {
        return $this->belongsTo(SessionPackagePurchase::class);
    }

    /**
     * Get the class session that consumed this credit, if it's a usage debit.
     */
    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    /**
     * Get the admin who made this entry, if manually adjusted.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
