<?php

namespace App\Filament\Resources\SessionPackagePurchases\Pages;

use App\Enums\SessionPackagePurchaseStatus;
use App\Filament\Resources\SessionPackagePurchases\SessionPackagePurchaseActions;
use App\Filament\Resources\SessionPackagePurchases\SessionPackagePurchaseResource;
use App\Models\SessionPackagePurchase;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSessionPackagePurchase extends ViewRecord
{
    protected static string $resource = SessionPackagePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verifyWithStripe')
                ->label('Verify with Stripe')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->visible(fn (SessionPackagePurchase $record) => $record->status !== SessionPackagePurchaseStatus::Paid)
                ->requiresConfirmation()
                ->action(function (SessionPackagePurchase $record) {
                    SessionPackagePurchaseActions::verifyWithStripe($record);
                }),
            Action::make('markAsPaid')
                ->label('Mark as Paid')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('warning')
                ->visible(fn (SessionPackagePurchase $record) => $record->status !== SessionPackagePurchaseStatus::Paid)
                ->requiresConfirmation()
                ->modalDescription('This credits the student without checking Stripe. Only do this if you\'ve confirmed payment some other way.')
                ->action(function (SessionPackagePurchase $record) {
                    SessionPackagePurchaseActions::markAsPaidManually($record);
                }),
        ];
    }
}
