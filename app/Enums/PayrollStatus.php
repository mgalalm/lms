<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PayrollStatus: string implements HasLabel
{
    case Pending = 'pending';
    case Paid = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
        };
    }
}
