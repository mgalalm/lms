<?php

namespace App\Services\Parents;

use App\Enums\ParentContactChannel;
use App\Enums\ParentNotificationChannel;
use App\Enums\ParentNotificationType;
use App\Models\ParentContact;

/**
 * Resolves a parent's channel preference for a notification type into the
 * actual, verified contact point(s) to send through. Shared by every
 * parent-facing send (Ping Parent today; future session_reminder/feedback/
 * progress_update sends) so the verified-only + fallback rules live in
 * exactly one place.
 */
class ParentNotificationRouter
{
    /**
     * @return list<ResolvedParentChannel> one entry per channel to attempt;
     *                                     'both' can yield up to two, and
     *                                     an empty result never happens —
     *                                     unresolvable cases return a
     *                                     single none() entry instead.
     */
    public function resolve(ParentContact $contact, ParentNotificationType $type): array
    {
        $preference = $contact->preferenceFor($type);

        if ($preference === ParentNotificationChannel::None) {
            return [ResolvedParentChannel::none('preference_none')];
        }

        $results = [];

        $wantsSms = in_array($preference, [ParentNotificationChannel::Sms, ParentNotificationChannel::Both], true);
        $wantsEmail = in_array($preference, [ParentNotificationChannel::Email, ParentNotificationChannel::Both], true);

        if ($wantsSms) {
            if ($contact->phone && $contact->phone_verified_at) {
                $results[] = ResolvedParentChannel::to(ParentContactChannel::Phone, $contact->phone);
            } elseif ($preference === ParentNotificationChannel::Sms && $contact->email && $contact->email_verified_at) {
                // SMS-only preference, but the phone is unverified/missing
                // — fall back to a verified email rather than dropping the
                // notification. Deliberately one-directional: an
                // email-only preference does not fall back to SMS.
                $results[] = ResolvedParentChannel::to(ParentContactChannel::Email, $contact->email, 'fallback_to_email');
            }
        }

        $alreadyResolvedEmail = collect($results)->contains(fn (ResolvedParentChannel $r) => $r->channel === ParentContactChannel::Email);

        if ($wantsEmail && ! $alreadyResolvedEmail && $contact->email && $contact->email_verified_at) {
            $results[] = ResolvedParentChannel::to(ParentContactChannel::Email, $contact->email);
        }

        return $results ?: [ResolvedParentChannel::none('no_verified_contact')];
    }
}
