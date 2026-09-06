<?php

namespace App\Models;

use App\Enums\ParentNotificationChannel;
use App\Enums\ParentNotificationType;
use App\Observers\ParentContactObserver;
use Database\Factories\ParentContactFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(ParentContactObserver::class)]
class ParentContact extends Model
{
    /** @use HasFactory<ParentContactFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * email_hash/phone_hash are deliberately absent — only
     * ParentContactObserver writes them, alongside the encrypted value
     * they index, so they can never drift out of sync with it.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'name',
        'email',
        'phone',
        'email_verified_at',
        'phone_verified_at',
        'consent_given_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Safeguarding contact data — encrypted at rest, same pattern
            // as User::name and the fields this table replaces on
            // StudentProfile. Unsearchable/unsortable at the database
            // level as a result; see email_hash/phone_hash above for the
            // one lookup this table needs to support anyway.
            'name' => 'encrypted',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'consent_given_at' => 'datetime',
        ];
    }

    /**
     * Get the student this is a parent/guardian contact for.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get this contact's per-notification-type channel preferences.
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(ParentNotificationPreference::class);
    }

    /**
     * Get this contact's verification-code history.
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(ParentContactVerification::class);
    }

    /**
     * The channel this parent has chosen for a given notification type,
     * falling back to that type's safe default when no preference row
     * exists yet (should only happen for a contact created before
     * seedDefaultPreferences() ran).
     */
    public function preferenceFor(ParentNotificationType $type): ParentNotificationChannel
    {
        return $this->notificationPreferences
            ->firstWhere('notification_type', $type)
            ?->channel ?? $type->defaultChannel();
    }

    /**
     * Seed every notification type's default channel preference for a
     * newly created contact. Marketing always starts at None — GDPR
     * requires separate, explicit opt-in, never an opt-out default.
     */
    public static function seedDefaultPreferences(self $contact): void
    {
        foreach (ParentNotificationType::cases() as $type) {
            $contact->notificationPreferences()->firstOrCreate(
                ['notification_type' => $type],
                ['channel' => $type->defaultChannel()],
            );
        }
    }
}
