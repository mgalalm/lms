<?php

namespace App\Filament\Resources\FlaggedMessages\Tables;

use App\Enums\MessageModerationStatus;
use App\Filament\Resources\FlaggedMessages\FlaggedMessageActions;
use App\Models\Message;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FlaggedMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'channel']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Sender')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('channel.name')
                    ->label('Channel')
                    ->placeholder('Direct message')
                    ->sortable(),
                TextColumn::make('body')
                    ->label('Message')
                    ->formatStateUsing(fn (?string $state) => $state ? strip_tags($state) : '—')
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('moderation_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (MessageModerationStatus $state) => match ($state) {
                        MessageModerationStatus::Flagged => 'danger',
                        MessageModerationStatus::Pending => 'warning',
                        MessageModerationStatus::Approved => 'success',
                    }),
                TextColumn::make('moderation_flags')
                    ->label('Flagged For')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('moderation_status')
                    ->options(MessageModerationStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Message $record) => $record->moderation_status !== MessageModerationStatus::Approved)
                    ->requiresConfirmation()
                    ->action(fn (Message $record) => FlaggedMessageActions::approve($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
