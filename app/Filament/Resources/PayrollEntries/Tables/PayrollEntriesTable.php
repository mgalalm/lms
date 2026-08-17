<?php

namespace App\Filament\Resources\PayrollEntries\Tables;

use App\Enums\PayrollStatus;
use App\Models\PayrollEntry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayrollEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Not ->searchable()/->sortable(): User.name is encrypted
                // at rest.
                TextColumn::make('teacherProfile.user.name')
                    ->label('Teacher'),
                TextColumn::make('month')
                    ->date('M Y')
                    ->sortable(),
                TextColumn::make('sessions_count')
                    ->label('Count'),
                TextColumn::make('basic_salary')
                    ->money('EGP'),
                TextColumn::make('net_amount')
                    ->label('Net Amount')
                    ->state(fn (PayrollEntry $record) => number_format($record->netAmount(), 2).' EGP'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (PayrollStatus $state) => $state === PayrollStatus::Paid ? 'success' : 'warning'),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PayrollStatus::class),
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
