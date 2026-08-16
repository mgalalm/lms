<?php

namespace App\Filament\Resources\StudentProfiles\Tables;

use App\Models\StudentProfile;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('timezone')
                    ->placeholder(StudentProfile::DEFAULT_TIMEZONE.' (default)')
                    ->sortable(),
                TextColumn::make('parent_email')
                    ->label('Parent Email')
                    ->placeholder('Not set')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('consent_given_at')
                    ->label('Consent')
                    ->boolean()
                    ->tooltip(fn (StudentProfile $record) => $record->consent_given_at?->toDayDateTimeString() ?? 'Not recorded'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
