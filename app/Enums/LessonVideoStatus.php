<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LessonVideoStatus: string implements HasLabel
{
    case Uploading = 'uploading';
    case Processing = 'processing';
    case Draft = 'draft';
    case Published = 'published';

    public function getLabel(): string
    {
        return match ($this) {
            self::Uploading => 'Uploading',
            self::Processing => 'Processing',
            self::Draft => 'Draft',
            self::Published => 'Published',
        };
    }
}
