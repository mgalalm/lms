<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LessonQuizQuestionType: string implements HasLabel
{
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';

    public function getLabel(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Multiple Choice',
            self::TrueFalse => 'True / False',
        };
    }
}
