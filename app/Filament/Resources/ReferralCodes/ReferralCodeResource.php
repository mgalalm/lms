<?php

namespace App\Filament\Resources\ReferralCodes;

use App\Filament\Resources\ReferralCodes\Pages\EditReferralCode;
use App\Filament\Resources\ReferralCodes\Pages\ListReferralCodes;
use App\Filament\Resources\ReferralCodes\RelationManagers\RefereeCreditsRelationManager;
use App\Filament\Resources\ReferralCodes\Schemas\ReferralCodeForm;
use App\Filament\Resources\ReferralCodes\Tables\ReferralCodesTable;
use App\Models\ReferralCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ReferralCodeResource extends Resource
{
    protected static ?string $model = ReferralCode::class;

    protected static ?string $navigationLabel = 'Referral Codes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 44;

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        // referrer.name excluded — User.name is encrypted at rest.
        return ['code', 'referrer.email'];
    }

    public static function getEloquentQuery(): Builder
    {
        // ledgerEntries is eager loaded (not withCount) because
        // "redemptions" means the referee side only, excluding the
        // referrer's own bonus-credit entry — refereeCredits() filters
        // by $this->referrer_id, which withCount can't correlate against
        // a real row (it builds the count query from a fresh, empty
        // model instance).
        return parent::getEloquentQuery()
            ->with(['referrer', 'ledgerEntries']);
    }

    /**
     * Codes are created for a user automatically on first request (see
     * ReferralCode::forUser()), never manually by an admin — this is a
     * usage report with an override for the mutable reward/limit fields,
     * not a place to fabricate new codes or erase redemption history.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ReferralCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReferralCodesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RefereeCreditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReferralCodes::route('/'),
            'edit' => EditReferralCode::route('/{record}/edit'),
        ];
    }
}
