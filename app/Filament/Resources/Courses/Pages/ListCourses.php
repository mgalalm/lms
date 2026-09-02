<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Resources\Courses\CourseResource;
use Filament\Resources\Pages\ListRecords;

class ListCourses extends ListRecords
{
    protected static string $resource = CourseResource::class;

    /**
     * No Create action — grade bands come from CourseSeeder, not ad-hoc
     * admin creation (see CourseResource::canCreate()).
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
