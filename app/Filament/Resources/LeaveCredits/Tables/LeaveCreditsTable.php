<?php

namespace App\Filament\Resources\LeaveCredits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeaveCreditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('teacherProfile.user.name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('monthly_used')
                    ->label('Monthly')
                    ->state(fn ($record) => "{$record->monthly_used}/{$record->monthly_limit} Days"),
                TextColumn::make('annual_used')
                    ->label('Annual')
                    ->state(fn ($record) => "{$record->annual_used}/{$record->annual_limit} Days"),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
