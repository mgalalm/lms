<?php

namespace App\Filament\Resources\TeacherTasks\Pages;

use App\Filament\Resources\TeacherTasks\TeacherTaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeacherTasks extends ListRecords
{
    protected static string $resource = TeacherTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
