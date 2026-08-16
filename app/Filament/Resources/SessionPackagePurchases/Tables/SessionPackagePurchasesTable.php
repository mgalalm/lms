<?php

namespace App\Filament\Resources\SessionPackagePurchases\Tables;

use App\Enums\SessionPackagePurchaseStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SessionPackagePurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'sessionPackage']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sessionPackage.name')
                    ->label('Package')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('session_count')
                    ->label('Sessions'),
                TextColumn::make('price_cents')
                    ->label('Price')
                    ->formatStateUsing(fn (int $state, $record) => number_format($state / 100, 2).' '.strtoupper($record->currency))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (SessionPackagePurchaseStatus $state) => match ($state) {
                        SessionPackagePurchaseStatus::Paid => 'success',
                        SessionPackagePurchaseStatus::Pending => 'warning',
                        SessionPackagePurchaseStatus::Cancelled => 'gray',
                        SessionPackagePurchaseStatus::Failed => 'danger',
                    }),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                        'failed' => 'Failed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
