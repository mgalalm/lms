<?php

namespace App\Filament\Resources\FlaggedMessages;

use App\Enums\MessageModerationStatus;
use App\Filament\Resources\FlaggedMessages\Pages\ListFlaggedMessages;
use App\Filament\Resources\FlaggedMessages\Pages\ViewFlaggedMessage;
use App\Filament\Resources\FlaggedMessages\Schemas\FlaggedMessageInfolist;
use App\Filament\Resources\FlaggedMessages\Tables\FlaggedMessagesTable;
use App\Models\Message;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class FlaggedMessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static ?string $slug = 'flagged-messages';

    protected static ?string $navigationLabel = 'Flagged Messages';

    protected static ?string $modelLabel = 'flagged message';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string|UnitEnum|null $navigationGroup = 'Safety';

    protected static ?int $navigationSort = 10;

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'body'];
    }

    /**
     * A focused review queue, not a general message browser — only
     * messages still awaiting or failing moderation ever need admin eyes.
     * Approved messages (the overwhelming majority of chat traffic) are
     * deliberately excluded.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('moderation_status', [
            MessageModerationStatus::Pending,
            MessageModerationStatus::Flagged,
        ]);
    }

    /**
     * Messages are never admin-authored — this is a review queue, not a
     * message composer.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()
            ->where('moderation_status', MessageModerationStatus::Flagged)
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function infolist(Schema $schema): Schema
    {
        return FlaggedMessageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FlaggedMessagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFlaggedMessages::route('/'),
            'view' => ViewFlaggedMessage::route('/{record}'),
        ];
    }
}
