<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SessionCreditReason: string implements HasLabel
{
    case Purchase = 'purchase';
    case SessionUsed = 'session_used';
    case Refund = 'refund';
    case Adjustment = 'adjustment';

    public function getLabel(): string
    {
        return match ($this) {
            self::Purchase => 'Package Purchased',
            self::SessionUsed => 'Session Scheduled',
            self::Refund => 'Refund',
            self::Adjustment => 'Manual Adjustment',
        };
    }
}
