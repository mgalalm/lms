<?php

namespace App\Filament\Resources\PointsLedgerEntries\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PointsLedgerEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('User')
                    ->options(fn () => User::role(['student', 'teacher'])->get()
                        ->mapWithKeys(fn (User $user) => [$user->id => "{$user->name} ({$user->roles->first()?->name})"]))
                    ->searchable()
                    ->required(),
                TextInput::make('points')
                    ->numeric()
                    ->required()
                    ->helperText('Positive to award points, negative to deduct.'),
                TextInput::make('reason')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}
