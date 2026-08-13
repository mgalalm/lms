<?php

namespace App\Filament\Resources\SessionPackages;

use App\Filament\Resources\SessionPackages\Pages\CreateSessionPackage;
use App\Filament\Resources\SessionPackages\Pages\EditSessionPackage;
use App\Filament\Resources\SessionPackages\Pages\ListSessionPackages;
use App\Filament\Resources\SessionPackages\Schemas\SessionPackageForm;
use App\Filament\Resources\SessionPackages\Tables\SessionPackagesTable;
use App\Models\SessionPackage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SessionPackageResource extends Resource
{
    protected static ?string $model = SessionPackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    public static function form(Schema $schema): Schema
    {
        return SessionPackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SessionPackagesTable::configure($table);
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
            'index' => ListSessionPackages::route('/'),
            'create' => CreateSessionPackage::route('/create'),
            'edit' => EditSessionPackage::route('/{record}/edit'),
        ];
    }
}
