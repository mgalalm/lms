<?php

namespace App\Filament\Resources\SessionPackages\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SessionPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                TextInput::make('session_count')
                    ->label('Session Count')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                TextInput::make('price_cents')
                    ->label('Price')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('€')
                    ->helperText('Enter the amount in cents, e.g. 15000 for €150.00.'),
                TextInput::make('currency')
                    ->required()
                    ->default('eur')
                    ->maxLength(3),
                TextInput::make('stripe_price_id')
                    ->label('Stripe Price ID')
                    ->helperText('Create a Price for this package in the Stripe dashboard and paste its ID here. Packages without a Price ID are hidden from students.')
                    ->maxLength(255),
                Toggle::make('active')
                    ->default(true),
            ]);
    }
}
