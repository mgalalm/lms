<?php

namespace App\Filament\Resources\ReferralCodes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RefereeCreditsRelationManager extends RelationManager
{
    protected static string $relationship = 'refereeCredits';

    protected static ?string $title = 'Redeemed By';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->recordTitleAttribute('id')
            ->columns([
                // user.name excluded — User.name is encrypted at rest.
                TextColumn::make('user.email')
                    ->label('Referee'),
                TextColumn::make('credits')
                    ->label('Credits Granted'),
                TextColumn::make('created_at')
                    ->label('Redeemed At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}
