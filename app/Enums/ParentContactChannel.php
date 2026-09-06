<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Which contact point on a ParentContact — used both for "which point is
 * this verification code for" and "which point did a send actually use".
 * Deliberately one enum for both, not two, to avoid duplicating the
 * email/phone distinction across the verification and delivery layers.
 */
enum ParentContactChannel: string implements HasLabel
{
    case Email = 'email';
    case Phone = 'phone';

    public function getLabel(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Phone => 'Phone',
        };
    }
}
