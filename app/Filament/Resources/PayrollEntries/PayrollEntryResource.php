<?php

namespace App\Filament\Resources\PayrollEntries;

use App\Filament\Resources\PayrollEntries\Pages\CreatePayrollEntry;
use App\Filament\Resources\PayrollEntries\Pages\EditPayrollEntry;
use App\Filament\Resources\PayrollEntries\Pages\ListPayrollEntries;
use App\Filament\Resources\PayrollEntries\RelationManagers\AdjustmentsRelationManager;
use App\Filament\Resources\PayrollEntries\Schemas\PayrollEntryForm;
use App\Filament\Resources\PayrollEntries\Tables\PayrollEntriesTable;
use App\Models\PayrollEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PayrollEntryResource extends Resource
{
    protected static ?string $model = PayrollEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Payroll & Requests';

    protected static ?int $navigationSort = 51;

    public static function form(Schema $schema): Schema
    {
        return PayrollEntryForm::configure($schema);
    }

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['teacherProfile.user.name', 'organization'];
    }

    public static function table(Table $table): Table
    {
        return PayrollEntriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AdjustmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollEntries::route('/'),
            'create' => CreatePayrollEntry::route('/create'),
            'edit' => EditPayrollEntry::route('/{record}/edit'),
        ];
    }
}
