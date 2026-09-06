<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ParentContact;
use App\Models\User;

class ParentContactPolicy
{
    /**
     * Whether a request may view/update this parent contact's details and
     * notification preferences. The magic-link page has no `auth`
     * middleware by design (parents get no account), so $user is often
     * null there — this is what stops a logged-in STUDENT who obtains a
     * validly-signed link (forwarded email, shared device) from viewing
     * or editing it. A UI-only hide can't catch that case; this is the
     * concrete "enforced at policy level" safeguarding control.
     */
    public function manage(?User $user, ParentContact $parentContact): bool
    {
        return $user === null || ! $user->hasRole(UserRole::Student->value);
    }
}
