<?php

namespace App\Filament\Resources\TeacherProfiles\Tables;

use App\Filament\Actions\ImpersonateAction;
use App\Models\TeacherProfile;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeacherProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Not ->searchable()/->sortable(): User.name is encrypted
                // at rest — search/sort via the user.email column instead.
                TextColumn::make('user.name')
                    ->label('Teacher'),
                TextColumn::make('user.email')
                    ->label('Teacher Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('timezone')
                    ->placeholder(TeacherProfile::DEFAULT_TIMEZONE.' (default)')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                ImpersonateAction::make(),
            ]);
    }
}
