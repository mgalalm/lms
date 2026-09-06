<?php

namespace App\Models;

use App\Enums\ParentContactChannel;
use Database\Factories\ParentContactVerificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentContactVerification extends Model
{
    /** @use HasFactory<ParentContactVerificationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'parent_contact_id',
        'channel',
        'code_hash',
        'destination',
        'attempts',
        'expires_at',
        'consumed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => ParentContactChannel::class,
            'destination' => 'encrypted',
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function parentContact(): BelongsTo
    {
        return $this->belongsTo(ParentContact::class);
    }
}
