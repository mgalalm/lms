<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * A parent's chosen delivery channel for one notification type. Distinct
 * from ParentContactChannel: this includes Both and None, which don't make
 * sense as "which contact point did this code verify".
 */
enum ParentNotificationChannel: string implements HasLabel
{
    case Sms = 'sms';
    case Email = 'email';
    case Both = 'both';
    case None = 'none';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sms => 'SMS',
            self::Email => 'Email',
            self::Both => 'Both',
            self::None => 'None',
        };
    }
}
