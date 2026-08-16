<?php

namespace App\Filament\Resources\SessionPackagePurchases\Tables;

use App\Enums\SessionPackagePurchaseStatus;
use App\Filament\Resources\SessionPackagePurchases\SessionPackagePurchaseActions;
use App\Models\SessionPackagePurchase;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
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
                ActionGroup::make([
                    Action::make('verifyWithStripe')
                        ->label('Verify with Stripe')
                        ->icon(Heroicon::OutlinedShieldCheck)
                        ->visible(fn (SessionPackagePurchase $record) => $record->status !== SessionPackagePurchaseStatus::Paid)
                        ->requiresConfirmation()
                        ->action(fn (SessionPackagePurchase $record) => SessionPackagePurchaseActions::verifyWithStripe($record)),
                    Action::make('markAsPaid')
                        ->label('Mark as Paid')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('warning')
                        ->visible(fn (SessionPackagePurchase $record) => $record->status !== SessionPackagePurchaseStatus::Paid)
                        ->requiresConfirmation()
                        ->modalDescription('This credits the student without checking Stripe. Only do this if you\'ve confirmed payment some other way.')
                        ->action(fn (SessionPackagePurchase $record) => SessionPackagePurchaseActions::markAsPaidManually($record)),
                ]),
            ]);
    }
}
