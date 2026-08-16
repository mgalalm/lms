<?php

namespace App\Filament\Resources\LeaveCredits;

use App\Filament\Resources\LeaveCredits\Pages\CreateLeaveCredit;
use App\Filament\Resources\LeaveCredits\Pages\EditLeaveCredit;
use App\Filament\Resources\LeaveCredits\Pages\ListLeaveCredits;
use App\Filament\Resources\LeaveCredits\Schemas\LeaveCreditForm;
use App\Filament\Resources\LeaveCredits\Tables\LeaveCreditsTable;
use App\Models\LeaveCredit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LeaveCreditResource extends Resource
{
    protected static ?string $model = LeaveCredit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Payroll & Requests';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return LeaveCreditForm::configure($schema);
    }

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['teacherProfile.user.name'];
    }

    public static function table(Table $table): Table
    {
        return LeaveCreditsTable::configure($table);
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
            'index' => ListLeaveCredits::route('/'),
            'create' => CreateLeaveCredit::route('/create'),
            'edit' => EditLeaveCredit::route('/{record}/edit'),
        ];
    }
}
