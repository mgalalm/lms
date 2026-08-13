<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SessionPackagePurchaseStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
            self::Failed => 'Failed',
        };
    }
}
