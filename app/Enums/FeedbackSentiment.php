<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FeedbackSentiment: string implements HasLabel
{
    case Positive = 'positive';
    case Neutral = 'neutral';
    case NeedsImprovement = 'needs_improvement';

    public function getLabel(): string
    {
        return match ($this) {
            self::Positive => 'Positive',
            self::Neutral => 'Neutral',
            self::NeedsImprovement => 'Needs Improvement',
        };
    }
}
