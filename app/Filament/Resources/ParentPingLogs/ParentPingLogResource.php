<?php

namespace App\Filament\Resources\ParentPingLogs;

use App\Enums\ParentPingDeliveryStatus;
use App\Filament\Resources\ParentPingLogs\Pages\ListParentPingLogs;
use App\Filament\Resources\ParentPingLogs\Pages\ViewParentPingLog;
use App\Filament\Resources\ParentPingLogs\Schemas\ParentPingLogInfolist;
use App\Filament\Resources\ParentPingLogs\Tables\ParentPingLogsTable;
use App\Models\ParentPingLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * A read-only audit trail for "Ping Parent" sends — admin visibility into
 * delivery status and cost, not a page anyone edits records from.
 */
class ParentPingLogResource extends Resource
{
    protected static ?string $model = ParentPingLog::class;

    protected static ?string $navigationLabel = 'Parent Pings';

    protected static ?string $modelLabel = 'parent ping';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|UnitEnum|null $navigationGroup = 'Safety';

    protected static ?int $navigationSort = 15;

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        // student.name/triggeredBy.name excluded — User.name is encrypted
        // at rest.
        return ['provider_message_id'];
    }

    public static function infolist(Schema $schema): Schema
    {
        return ParentPingLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ParentPingLogsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->where('status', ParentPingDeliveryStatus::NoVerifiedContact)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return $count ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
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
            'index' => ListParentPingLogs::route('/'),
            'view' => ViewParentPingLog::route('/{record}'),
        ];
    }
}
