<?php

namespace App\Services\Parents;

use App\Enums\ParentContactChannel;
use App\Enums\ParentContactVerificationResult;
use App\Exceptions\Parents\TooManyVerificationSendsException;
use App\Models\ParentContact;
use App\Models\ParentContactVerification;
use App\Notifications\ParentContactVerificationCode;
use App\Services\Sms\SmsProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The single orchestrator for "send a 6-digit code" / "check a 6-digit
 * code", used by every entry point that can add or change a parent contact
 * point: registration, Filament admin, and the parent's own magic-link
 * page. Callers never talk to ParentContactVerification directly.
 */
class ContactVerificationService
{
    private const CODE_TTL_MINUTES = 15;

    private const MAX_SENDS_PER_HOUR = 3;

    private const MAX_VERIFY_ATTEMPTS = 5;

    public function __construct(private readonly SmsProvider $sms) {}

    /**
     * @throws TooManyVerificationSendsException
     */
    public function sendCode(ParentContact $contact, ParentContactChannel $channel): void
    {
        $destination = $channel === ParentContactChannel::Email ? $contact->email : $contact->phone;

        if (! $destination) {
            return;
        }

        $rateLimitKey = "parent-verify-send:{$contact->id}:{$channel->value}";

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_SENDS_PER_HOUR)) {
            throw new TooManyVerificationSendsException(RateLimiter::availableIn($rateLimitKey));
        }

        RateLimiter::hit($rateLimitKey, 3600);

        $code = (string) random_int(100000, 999999);

        $contact->verifications()->create([
            'channel' => $channel,
            'code_hash' => Hash::make($code),
            'destination' => $destination,
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);

        if ($channel === ParentContactChannel::Email) {
            Notification::route('mail', $destination)->notify(new ParentContactVerificationCode($code));

            return;
        }

        $this->sms->send($destination, "Your verification code is {$code}. It expires in 15 minutes.");
    }

    /**
     * Verify a submitted code against the latest unconsumed one for this
     * contact point. On success, marks the matching *_verified_at on the
     * hydrated $contact instance passed in — callers must never instead
     * do $contact->parentContact()->update(...), which bypasses the
     * `encrypted` cast (see .ai/rules/models.md).
     */
    public function verifyCode(ParentContact $contact, ParentContactChannel $channel, string $code): ParentContactVerificationResult
    {
        $verification = $contact->verifications()
            ->where('channel', $channel)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $verification) {
            return ParentContactVerificationResult::NoPendingCode;
        }

        if ($verification->attempts >= self::MAX_VERIFY_ATTEMPTS) {
            $verification->update(['consumed_at' => now()]);

            return ParentContactVerificationResult::TooManyAttempts;
        }

        if ($verification->expires_at->isPast()) {
            return ParentContactVerificationResult::Expired;
        }

        if (! Hash::check($code, $verification->code_hash)) {
            $verification->increment('attempts');

            return ParentContactVerificationResult::Incorrect;
        }

        $verification->update(['consumed_at' => now()]);

        $contact->update([
            $channel === ParentContactChannel::Email ? 'email_verified_at' : 'phone_verified_at' => now(),
        ]);

        return ParentContactVerificationResult::Verified;
    }
}
