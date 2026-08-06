<?php

namespace App\Filament\Resources\TeacherRequests\Pages;

use App\Filament\Resources\TeacherRequests\TeacherRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeacherRequests extends ListRecords
{
    protected static string $resource = TeacherRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
