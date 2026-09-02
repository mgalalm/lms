<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\EditRecord;

class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    /**
     * No Delete action — grade bands come from CourseSeeder, not
     * ad-hoc-deletable by admins (see CourseResource::canDelete()).
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
