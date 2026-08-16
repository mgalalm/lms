<?php

namespace App\Filament\Resources\PointsLedgerEntries;

use App\Filament\Resources\PointsLedgerEntries\Pages\CreatePointsLedgerEntry;
use App\Filament\Resources\PointsLedgerEntries\Pages\EditPointsLedgerEntry;
use App\Filament\Resources\PointsLedgerEntries\Pages\ListPointsLedgerEntries;
use App\Filament\Resources\PointsLedgerEntries\Schemas\PointsLedgerEntryForm;
use App\Filament\Resources\PointsLedgerEntries\Tables\PointsLedgerEntriesTable;
use App\Models\PointsLedgerEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PointsLedgerEntryResource extends Resource
{
    protected static ?string $model = PointsLedgerEntry::class;

    protected static ?string $navigationLabel = 'Points Ledger';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Gamification';

    protected static ?int $navigationSort = 71;

    public static function form(Schema $schema): Schema
    {
        return PointsLedgerEntryForm::configure($schema);
    }

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'reason'];
    }

    public static function table(Table $table): Table
    {
        return PointsLedgerEntriesTable::configure($table);
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
            'index' => ListPointsLedgerEntries::route('/'),
            'create' => CreatePointsLedgerEntry::route('/create'),
            'edit' => EditPointsLedgerEntry::route('/{record}/edit'),
        ];
    }
}
