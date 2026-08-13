<?php

namespace App\Filament\Resources\SessionPackagePurchases\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SessionPackagePurchaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('Student'),
                TextEntry::make('sessionPackage.name')
                    ->label('Package'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('session_count')
                    ->label('Sessions'),
                TextEntry::make('price_cents')
                    ->label('Price')
                    ->formatStateUsing(fn (int $state, $record) => number_format($state / 100, 2).' '.strtoupper($record->currency)),
                TextEntry::make('stripe_checkout_session_id')
                    ->label('Stripe Checkout Session')
                    ->placeholder('—'),
                TextEntry::make('paid_at')
                    ->dateTime()
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
