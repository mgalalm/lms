<?php

namespace App\Observers;

use App\Enums\ParentContactChannel;
use App\Exceptions\Parents\TooManyVerificationSendsException;
use App\Models\ParentContact;
use App\Services\Parents\ContactVerificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ParentContactObserver
{
    public function __construct(private readonly ContactVerificationService $verification) {}

    /**
     * Recompute the blind-index hash columns whenever the plaintext they
     * index changes, and drop verification status the moment a verified
     * contact point's value changes — a changed email/phone is, by
     * definition, unverified until proven again.
     */
    public function saving(ParentContact $contact): void
    {
        if ($contact->isDirty('email')) {
            $contact->email_hash = $contact->email ? self::hash($contact->email) : null;

            if ($contact->exists) {
                $contact->email_verified_at = null;
            }
        }

        if ($contact->isDirty('phone')) {
            $contact->phone_hash = $contact->phone ? self::hash($contact->phone) : null;

            if ($contact->exists) {
                $contact->phone_verified_at = null;
            }
        }
    }

    /**
     * A brand-new contact: seed its notification preferences and send a
     * verification code to every contact point it was created with.
     */
    public function created(ParentContact $contact): void
    {
        ParentContact::seedDefaultPreferences($contact);

        if ($contact->email) {
            $this->sendCode($contact, ParentContactChannel::Email);
        }

        if ($contact->phone) {
            $this->sendCode($contact, ParentContactChannel::Phone);
        }
    }

    /**
     * An existing contact whose email/phone just changed to a non-null
     * value needs a fresh code. The `!== null` guard is what makes
     * User::gdprErase() safe — nulling a contact point must never trigger
     * a send.
     */
    public function updated(ParentContact $contact): void
    {
        if ($contact->wasChanged('email') && $contact->email !== null) {
            $this->sendCode($contact, ParentContactChannel::Email);
        }

        if ($contact->wasChanged('phone') && $contact->phone !== null) {
            $this->sendCode($contact, ParentContactChannel::Phone);
        }
    }

    /**
     * An implicit, background-triggered send (registration, an admin
     * editing a record, GDPR erasure) must never fail the save it's
     * riding along with — just log and move on. A parent/admin explicitly
     * pressing "resend" goes through ContactVerificationService directly
     * and DOES see the exception.
     */
    private function sendCode(ParentContact $contact, ParentContactChannel $channel): void
    {
        try {
            $this->verification->sendCode($contact, $channel);
        } catch (TooManyVerificationSendsException $e) {
            Log::warning('Skipped parent contact verification send: rate limited.', [
                'parent_contact_id' => $contact->id,
                'channel' => $channel->value,
                'retry_after_seconds' => $e->retryAfterSeconds,
            ]);
        }
    }

    private static function hash(string $value): string
    {
        return hash_hmac('sha256', $value, config('app.key'));
    }
}
