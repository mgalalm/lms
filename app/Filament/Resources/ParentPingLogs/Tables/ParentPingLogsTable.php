<?php

namespace App\Filament\Resources\ParentPingLogs\Tables;

use App\Enums\ParentContactChannel;
use App\Enums\ParentPingDeliveryStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ParentPingLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['student', 'triggeredBy']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                // Not ->searchable(): User.name is encrypted at rest.
                TextColumn::make('student.name')
                    ->label('Student'),
                TextColumn::make('triggeredBy.name')
                    ->label('Teacher'),
                TextColumn::make('channel')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn (?ParentContactChannel $state) => $state === ParentContactChannel::Phone ? 'info' : 'gray'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ParentPingDeliveryStatus $state) => match ($state) {
                        ParentPingDeliveryStatus::Sent => 'success',
                        ParentPingDeliveryStatus::Failed => 'danger',
                        ParentPingDeliveryStatus::NoVerifiedContact => 'warning',
                    }),
                TextColumn::make('provider_message_id')
                    ->label('Provider Message ID')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('provider_status')
                    ->label('Provider Status')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ParentPingDeliveryStatus::class),
                SelectFilter::make('channel')
                    ->options(ParentContactChannel::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
