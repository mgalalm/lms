<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Filament\Resources\Activities\Tables\ActivitiesTable;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Access Details')
                    ->columns(2)
                    ->components([
                        TextEntry::make('causer.name')
                            ->label('Accessed By')
                            ->placeholder('System'),
                        TextEntry::make('causer.email')
                            ->label('Email')
                            ->placeholder('—'),
                        TextEntry::make('description')
                            ->label('Action'),
                        TextEntry::make('subject_type')
                            ->label('Subject Type')
                            ->badge()
                            ->formatStateUsing(fn (?string $state) => ActivitiesTable::SUBJECT_TYPE_LABELS[$state] ?? $state),
                        TextEntry::make('subject_id')
                            ->label('Subject ID'),
                        TextEntry::make('properties.ip')
                            ->label('IP Address'),
                        TextEntry::make('created_at')
                            ->label('When')
                            ->dateTime(),
                    ]),
                Section::make('Full Properties')
                    ->description('Everything recorded alongside this access, including the IP address above.')
                    ->components([
                        KeyValueEntry::make('properties')
                            ->hiddenLabel(),
                    ]),
            ]);
    }
}
