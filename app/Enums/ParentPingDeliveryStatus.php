<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ParentPingDeliveryStatus: string implements HasLabel
{
    case Sent = 'sent';
    case Failed = 'failed';
    case NoVerifiedContact = 'no_verified_contact';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sent => 'Sent',
            self::Failed => 'Failed',
            self::NoVerifiedContact => 'No verified contact',
        };
    }
}
