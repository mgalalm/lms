<?php

namespace App\Filament\Resources\PointsLedgerEntries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PointsLedgerEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Not ->searchable()/->sortable(): User.name is encrypted
                // at rest.
                TextColumn::make('user.name')
                    ->label('User'),
                TextColumn::make('points')
                    ->sortable()
                    ->color(fn (int $state) => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('reason')
                    ->limit(60),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
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
