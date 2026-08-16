<?php

namespace App\Filament\Resources\SessionPackagePurchases;

use App\Filament\Resources\SessionPackagePurchases\Pages\ListSessionPackagePurchases;
use App\Filament\Resources\SessionPackagePurchases\Pages\ViewSessionPackagePurchase;
use App\Filament\Resources\SessionPackagePurchases\Schemas\SessionPackagePurchaseInfolist;
use App\Filament\Resources\SessionPackagePurchases\Tables\SessionPackagePurchasesTable;
use App\Models\SessionPackagePurchase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SessionPackagePurchaseResource extends Resource
{
    protected static ?string $model = SessionPackagePurchase::class;

    protected static ?string $navigationLabel = 'Purchases';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 41;

    /**
     * Purchases are created only via the student checkout flow and paid
     * only via the Stripe webhook — no manual creation from the admin panel.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'sessionPackage.name'];
    }

    public static function infolist(Schema $schema): Schema
    {
        return SessionPackagePurchaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SessionPackagePurchasesTable::configure($table);
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
            'index' => ListSessionPackagePurchases::route('/'),
            'view' => ViewSessionPackagePurchase::route('/{record}'),
        ];
    }
}
