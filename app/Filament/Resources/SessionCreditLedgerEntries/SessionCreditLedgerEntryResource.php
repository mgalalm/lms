<?php

namespace App\Filament\Resources\SessionCreditLedgerEntries;

use App\Filament\Resources\SessionCreditLedgerEntries\Pages\CreateSessionCreditLedgerEntry;
use App\Filament\Resources\SessionCreditLedgerEntries\Pages\EditSessionCreditLedgerEntry;
use App\Filament\Resources\SessionCreditLedgerEntries\Pages\ListSessionCreditLedgerEntries;
use App\Filament\Resources\SessionCreditLedgerEntries\Schemas\SessionCreditLedgerEntryForm;
use App\Filament\Resources\SessionCreditLedgerEntries\Tables\SessionCreditLedgerEntriesTable;
use App\Models\SessionCreditLedgerEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SessionCreditLedgerEntryResource extends Resource
{
    protected static ?string $model = SessionCreditLedgerEntry::class;

    protected static ?string $navigationLabel = 'Session Credits Ledger';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 42;

    public static function form(Schema $schema): Schema
    {
        return SessionCreditLedgerEntryForm::configure($schema);
    }

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        // user.name excluded — User.name is encrypted at rest.
        return ['user.email', 'reason'];
    }

    public static function table(Table $table): Table
    {
        return SessionCreditLedgerEntriesTable::configure($table);
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
            'index' => ListSessionCreditLedgerEntries::route('/'),
            'create' => CreateSessionCreditLedgerEntry::route('/create'),
            'edit' => EditSessionCreditLedgerEntry::route('/{record}/edit'),
        ];
    }
}
