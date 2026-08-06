<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FlagSeverity: string implements HasLabel
{
    case Green = 'green';
    case Yellow = 'yellow';
    case Red = 'red';

    public function getLabel(): string
    {
        return match ($this) {
            self::Green => 'Green',
            self::Yellow => 'Yellow',
            self::Red => 'Red',
        };
    }
}
