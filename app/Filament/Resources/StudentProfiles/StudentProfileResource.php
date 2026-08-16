<?php

namespace App\Filament\Resources\StudentProfiles;

use App\Filament\Resources\StudentProfiles\Pages\EditStudentProfile;
use App\Filament\Resources\StudentProfiles\Pages\ListStudentProfiles;
use App\Filament\Resources\StudentProfiles\Schemas\StudentProfileForm;
use App\Filament\Resources\StudentProfiles\Tables\StudentProfilesTable;
use App\Models\StudentProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class StudentProfileResource extends Resource
{
    protected static ?string $model = StudentProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Sessions';

    protected static ?string $navigationLabel = 'Student Profiles';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return StudentProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentProfilesTable::configure($table);
    }

    /**
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['user.name', 'code', 'parent_email'];
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
            'index' => ListStudentProfiles::route('/'),
            'edit' => EditStudentProfile::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
