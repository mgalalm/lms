<?php

namespace App\Filament\Resources\FlaggedMessages\Schemas;

use App\Enums\MessageModerationStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FlaggedMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('Sender'),
                TextEntry::make('channel.name')
                    ->label('Channel')
                    ->placeholder('Direct message'),
                TextEntry::make('moderation_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (MessageModerationStatus $state) => match ($state) {
                        MessageModerationStatus::Flagged => 'danger',
                        MessageModerationStatus::Pending => 'warning',
                        MessageModerationStatus::Approved => 'success',
                    }),
                TextEntry::make('moderation_flags')
                    ->label('Flagged For')
                    ->badge()
                    ->placeholder('—'),
                TextEntry::make('body')
                    ->label('Message')
                    ->formatStateUsing(fn (?string $state) => $state ? strip_tags($state) : '—')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Sent')
                    ->dateTime(),
                TextEntry::make('moderated_at')
                    ->dateTime()
                    ->placeholder('Not yet reviewed'),
            ]);
    }
}
