<?php

namespace App\Filament\Resources\TeacherRequests\Pages;

use App\Filament\Resources\TeacherRequests\TeacherRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTeacherRequest extends EditRecord
{
    protected static string $resource = TeacherRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
