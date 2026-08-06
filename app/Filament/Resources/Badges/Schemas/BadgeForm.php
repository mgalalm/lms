<?php

namespace App\Filament\Resources\Badges\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BadgeForm
{
    public const ICONS = [
        'trophy' => 'Trophy',
        'star' => 'Star',
        'zap' => 'Lightning',
        'award' => 'Award',
        'target' => 'Target',
        'flame' => 'Flame',
        'medal' => 'Medal',
        'rocket' => 'Rocket',
        'clock' => 'Clock',
        'graduation-cap' => 'Graduation Cap',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('description')
                    ->required()
                    ->maxLength(255),
                Select::make('icon')
                    ->options(self::ICONS)
                    ->required(),
            ]);
    }
}
