<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MessageModerationStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Flagged = 'flagged';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            self::Approved => 'Approved',
            self::Flagged => 'Flagged',
        };
    }
}
