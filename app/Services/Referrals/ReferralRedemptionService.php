<?php

namespace App\Services\Referrals;

use App\Enums\SessionCreditReason;
use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReferralRedemptionService
{
    /**
     * Whether the given code string currently identifies a code this user
     * could redeem — valid, not expired/exhausted, and not their own.
     * Used both for live validation (registration/checkout UI) and as the
     * first check inside redeem() itself. $user is null during
     * registration (the account doesn't exist yet), which simply skips
     * the self-referral check — a brand new user can't own a code yet.
     */
    public function findRedeemableFor(?User $user, string $code): ?ReferralCode
    {
        $referralCode = ReferralCode::where('code', $code)->first();

        if (! $referralCode || ! $referralCode->isRedeemable()) {
            return null;
        }

        if ($user && $referralCode->referrer_id === $user->id) {
            return null;
        }

        return $referralCode;
    }

    /**
     * Redeem a referral code for a user who hasn't redeemed one before —
     * credits both the referee (this user) and the referrer through the
     * existing session credit ledger, and decrements uses_remaining.
     * Shared by registration and first-checkout so both paths credit
     * exactly the same way. Silently does nothing (returns false) for any
     * invalid state rather than throwing, since a referral code is always
     * optional and should never block the flow that's redeeming it.
     */
    public function redeem(User $user, string $code): bool
    {
        if ($this->hasAlreadyRedeemed($user)) {
            return false;
        }

        $referralCode = $this->findRedeemableFor($user, $code);

        if (! $referralCode) {
            return false;
        }

        DB::transaction(function () use ($user, $referralCode) {
            $user->sessionCreditLedger()->create([
                'credits' => $referralCode->reward_sessions_referee,
                'reason' => SessionCreditReason::Referral,
                'referral_code_id' => $referralCode->id,
            ]);

            $referralCode->referrer->sessionCreditLedger()->create([
                'credits' => $referralCode->reward_sessions_referrer,
                'reason' => SessionCreditReason::Referral,
                'referral_code_id' => $referralCode->id,
            ]);

            if ($referralCode->uses_remaining !== null) {
                $referralCode->decrement('uses_remaining');
            }
        });

        return true;
    }

    /**
     * A user may only ever redeem one referral code, whether at signup or
     * on their first checkout — this is what stops the same person
     * re-entering a code on every purchase to farm bonus sessions.
     */
    public function hasAlreadyRedeemed(User $user): bool
    {
        return $user->sessionCreditLedger()
            ->where('reason', SessionCreditReason::Referral)
            ->exists();
    }
}
