<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SessionType: string implements HasLabel
{
    case Online = 'online';
    case Physical = 'physical';

    public function getLabel(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Physical => 'Physical',
        };
    }
}
