<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('grade_band')
                    ->placeholder('e.g. Grade 5-6'),
                FileUpload::make('thumbnail_path')
                    ->label('Thumbnail')
                    ->image()
                    ->disk('public')
                    ->directory('course-thumbnails'),
            ]);
    }
}
