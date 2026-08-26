<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProjectSubmissionStatus: string implements HasLabel
{
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Reviewed => 'Reviewed',
        };
    }
}
