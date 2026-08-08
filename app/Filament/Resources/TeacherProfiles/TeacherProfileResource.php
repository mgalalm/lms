<?php

namespace App\Filament\Resources\TeacherProfiles;

use App\Filament\Resources\TeacherProfiles\Pages\EditTeacherProfile;
use App\Filament\Resources\TeacherProfiles\Pages\ListTeacherProfiles;
use App\Filament\Resources\TeacherProfiles\Schemas\TeacherProfileForm;
use App\Filament\Resources\TeacherProfiles\Tables\TeacherProfilesTable;
use App\Models\TeacherProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TeacherProfileResource extends Resource
{
    protected static ?string $model = TeacherProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Sessions';

    protected static ?string $navigationLabel = 'Teacher Profiles';

    public static function form(Schema $schema): Schema
    {
        return TeacherProfileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeacherProfilesTable::configure($table);
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
            'index' => ListTeacherProfiles::route('/'),
            'edit' => EditTeacherProfile::route('/{record}/edit'),
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
