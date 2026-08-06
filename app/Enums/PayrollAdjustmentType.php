<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PayrollAdjustmentType: string implements HasLabel
{
    case Bonus = 'bonus';
    case Deduction = 'deduction';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bonus => 'Bonus',
            self::Deduction => 'Deduction',
        };
    }
}
