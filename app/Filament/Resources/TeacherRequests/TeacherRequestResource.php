<?php

namespace App\Filament\Resources\TeacherRequests;

use App\Filament\Resources\TeacherRequests\Pages\CreateTeacherRequest;
use App\Filament\Resources\TeacherRequests\Pages\EditTeacherRequest;
use App\Filament\Resources\TeacherRequests\Pages\ListTeacherRequests;
use App\Filament\Resources\TeacherRequests\Schemas\TeacherRequestForm;
use App\Filament\Resources\TeacherRequests\Tables\TeacherRequestsTable;
use App\Models\TeacherRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TeacherRequestResource extends Resource
{
    protected static ?string $model = TeacherRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Payroll & Requests';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['teacherProfile.user', 'reviewer']);
    }

    public static function form(Schema $schema): Schema
    {
        return TeacherRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeacherRequestsTable::configure($table);
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
            'index' => ListTeacherRequests::route('/'),
            'create' => CreateTeacherRequest::route('/create'),
            'edit' => EditTeacherRequest::route('/{record}/edit'),
        ];
    }
}
