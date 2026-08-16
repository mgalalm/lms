<?php

namespace App\Filament\Resources\StudentProfiles\Tables;

use App\Filament\Resources\StudentProfiles\StudentProfileActions;
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
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Student Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('timezone')
                    ->placeholder(StudentProfile::DEFAULT_TIMEZONE.' (default)')
                    ->sortable(),
                TextColumn::make('parent_name')
                    ->label('Parent/Guardian Name')
                    ->placeholder('Not set')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('parent_email')
                    ->label('Parent/Guardian Email')
                    ->placeholder('Not set')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('parent_phone')
                    ->label('Parent/Guardian Phone')
                    ->placeholder('Not set')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('consent_given_at')
                    ->label('Consent')
                    ->boolean()
                    ->tooltip(fn (StudentProfile $record) => $record->consent_given_at?->toDayDateTimeString() ?? 'Not recorded'),
                IconColumn::make('user.gdpr_erased_at')
                    ->label('Erased')
                    ->boolean()
                    ->trueColor('danger'),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (StudentProfile $record) => ! $record->user->gdpr_erased_at),
                StudentProfileActions::eraseAction(),
            ]);
    }
}
