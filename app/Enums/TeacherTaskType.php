<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TeacherTaskType: string implements HasLabel
{
    case SessionPrep = 'session_prep';
    case SessionReview = 'session_review';
    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::SessionPrep => 'Session Prep',
            self::SessionReview => 'Session Review',
            self::Manual => 'Manual',
        };
    }
}
