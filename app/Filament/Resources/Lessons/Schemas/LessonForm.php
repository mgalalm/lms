<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Models\Lesson;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('module_id')
                    ->relationship('module', 'title')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('reading_content')
                    ->columnSpanFull(),
                Section::make('Teaching Video')
                    ->description('Curriculum teaching video for this lesson — distinct from session recap recordings. Use the Upload/Replace and Publish buttons above.')
                    ->schema([
                        Placeholder::make('video_summary')
                            ->hiddenLabel()
                            ->content(function (?Lesson $record) {
                                $video = $record?->video;

                                if (! $video) {
                                    return 'No video uploaded yet.';
                                }

                                $summary = $video->status->getLabel();

                                if ($video->duration_seconds) {
                                    $summary .= ' · '.gmdate('i:s', $video->duration_seconds);
                                }

                                return $summary;
                            }),
                    ])
                    ->hiddenOn('create')
                    ->columnSpanFull(),
            ]);
    }
}
