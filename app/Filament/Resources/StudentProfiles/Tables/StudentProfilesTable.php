<?php

namespace App\Filament\Resources\StudentProfiles\Tables;

use App\Filament\Actions\ImpersonateAction;
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
                // Not ->searchable()/->sortable(): User.name is encrypted
                // at rest — search/sort via the user.email column instead.
                TextColumn::make('user.name')
                    ->label('Student'),
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
                IconColumn::make('user.gdpr_erased_at')
                    ->label('Erased')
                    ->boolean()
                    ->trueColor('danger'),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (StudentProfile $record) => ! $record->user->gdpr_erased_at),
                ImpersonateAction::make(),
                StudentProfileActions::eraseAction(),
            ]);
    }
}
