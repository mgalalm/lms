<?php

namespace App\Exceptions\Parents;

use RuntimeException;

/**
 * Thrown when a contact point has already had 3 verification codes sent to
 * it within the last hour. Carries how long until another send is allowed
 * so callers (Filament actions, the magic-link page) can surface a
 * specific, actionable message instead of a generic failure.
 */
class TooManyVerificationSendsException extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        $minutes = (int) ceil($retryAfterSeconds / 60);

        parent::__construct("Too many verification codes sent recently. Try again in {$minutes} minute(s).");
    }
}
