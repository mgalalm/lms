<?php

namespace App\Filament\Resources\ReferralCodes\Tables;

use App\Models\ReferralCode;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReferralCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->weight('bold')
                    ->searchable(),
                // referrer.name excluded — User.name is encrypted at rest.
                TextColumn::make('referrer.email')
                    ->label('Referrer')
                    ->searchable(),
                TextColumn::make('redemptions')
                    ->label('Redemptions')
                    ->getStateUsing(fn (ReferralCode $record) => $record->ledgerEntries
                        ->where('user_id', '!=', $record->referrer_id)
                        ->count()),
                TextColumn::make('reward_sessions_referrer')
                    ->label('Referrer Gets'),
                TextColumn::make('reward_sessions_referee')
                    ->label('Referee Gets'),
                TextColumn::make('uses_remaining')
                    ->label('Uses Left')
                    ->placeholder('Unlimited'),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
