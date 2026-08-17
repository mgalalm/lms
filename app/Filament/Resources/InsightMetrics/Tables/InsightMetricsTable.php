<?php

namespace App\Filament\Resources\InsightMetrics\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InsightMetricsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Not ->searchable()/->sortable(): User.name is encrypted
                // at rest.
                TextColumn::make('teacherProfile.user.name')
                    ->label('Teacher'),
                TextColumn::make('period_start')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_end')
                    ->date()
                    ->sortable(),
                TextColumn::make('objections')
                    ->sortable(),
                TextColumn::make('excuses')
                    ->sortable(),
                TextColumn::make('study_level')
                    ->sortable(),
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
