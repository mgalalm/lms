<?php

namespace App\Filament\Resources\SessionCreditLedgerEntries\Schemas;

use App\Enums\SessionCreditReason;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SessionCreditLedgerEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Student')
                    ->options(fn () => User::role('student')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                TextInput::make('credits')
                    ->numeric()
                    ->required()
                    ->helperText('Positive to add credits, negative to deduct.'),
                Select::make('reason')
                    ->options([
                        SessionCreditReason::Adjustment->value => SessionCreditReason::Adjustment->getLabel(),
                        SessionCreditReason::Refund->value => SessionCreditReason::Refund->getLabel(),
                    ])
                    ->default(SessionCreditReason::Adjustment->value)
                    ->required(),
            ]);
    }
}
