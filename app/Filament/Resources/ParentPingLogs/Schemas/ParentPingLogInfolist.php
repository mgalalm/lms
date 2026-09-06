<?php

namespace App\Filament\Resources\ParentPingLogs\Schemas;

use App\Enums\ParentPingDeliveryStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ParentPingLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('student.name')
                    ->label('Student'),
                TextEntry::make('triggeredBy.name')
                    ->label('Teacher'),
                TextEntry::make('channel')
                    ->badge()
                    ->placeholder('—'),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn (ParentPingDeliveryStatus $state) => match ($state) {
                        ParentPingDeliveryStatus::Sent => 'success',
                        ParentPingDeliveryStatus::Failed => 'danger',
                        ParentPingDeliveryStatus::NoVerifiedContact => 'warning',
                    }),
                TextEntry::make('provider_message_id')
                    ->label('Provider Message ID')
                    ->placeholder('—'),
                TextEntry::make('provider_status')
                    ->label('Provider Status')
                    ->placeholder('—'),
                TextEntry::make('message')
                    ->columnSpanFull(),
                TextEntry::make('error_detail')
                    ->label('Error')
                    ->placeholder('—')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label('Sent')
                    ->dateTime(),
            ]);
    }
}
