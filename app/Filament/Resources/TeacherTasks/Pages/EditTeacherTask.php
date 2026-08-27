<?php

namespace App\Filament\Resources\TeacherTasks\Pages;

use App\Filament\Resources\TeacherTasks\TeacherTaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTeacherTask extends EditRecord
{
    protected static string $resource = TeacherTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
