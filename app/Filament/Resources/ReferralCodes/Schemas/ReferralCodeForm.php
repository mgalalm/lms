<?php

namespace App\Filament\Resources\ReferralCodes\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ReferralCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->disabled(),
                TextInput::make('referrer.email')
                    ->label('Referrer')
                    ->disabled(),
                TextInput::make('reward_sessions_referrer')
                    ->label('Reward — Referrer (sessions)')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('reward_sessions_referee')
                    ->label('Reward — Referee (sessions)')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                TextInput::make('uses_remaining')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('Leave blank for unlimited uses.'),
                DateTimePicker::make('expires_at')
                    ->helperText('Leave blank for a code that never expires.'),
            ]);
    }
}
