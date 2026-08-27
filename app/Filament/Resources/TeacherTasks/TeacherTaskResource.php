<?php

namespace App\Filament\Resources\TeacherTasks;

use App\Filament\Resources\TeacherTasks\Pages\CreateTeacherTask;
use App\Filament\Resources\TeacherTasks\Pages\EditTeacherTask;
use App\Filament\Resources\TeacherTasks\Pages\ListTeacherTasks;
use App\Filament\Resources\TeacherTasks\Schemas\TeacherTaskForm;
use App\Filament\Resources\TeacherTasks\Tables\TeacherTasksTable;
use App\Models\TeacherTask;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TeacherTaskResource extends Resource
{
    protected static ?string $model = TeacherTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Sessions';

    protected static ?int $navigationSort = 22;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return TeacherTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeacherTasksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeacherTasks::route('/'),
            'create' => CreateTeacherTask::route('/create'),
            'edit' => EditTeacherTask::route('/{record}/edit'),
        ];
    }
}
