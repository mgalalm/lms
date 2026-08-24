<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Models\Lesson;
use App\Services\Bunny\BunnyStreamClient;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

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
                        Placeholder::make('video_preview')
                            ->hiddenLabel()
                            ->content(function (?Lesson $record) {
                                $video = $record?->video;

                                if (! $video || ! $video->bunny_video_id) {
                                    return null;
                                }

                                $url = app(BunnyStreamClient::class)->signedPlaybackUrl($video->bunny_video_id);

                                return new HtmlString(
                                    '<iframe src="'.e($url).'" class="aspect-video w-full max-w-xl rounded-lg" '
                                    .'allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>'
                                );
                            }),
                    ])
                    ->hiddenOn('create')
                    ->columnSpanFull(),
                Section::make('Tutor Guide')
                    ->description('Teacher/admin prep material for this lesson — never shown to students, under any circumstance.')
                    ->schema([
                        MarkdownEditor::make('tutor_guide_intro')
                            ->label('Intro')
                            ->fileAttachmentsDisk('bunny')
                            ->fileAttachmentsDirectory(fn (?Lesson $record) => 'lesson-tutor-guides/'.($record?->id ?? 'new').'/images')
                            ->columnSpanFull(),
                        Repeater::make('tutor_guide_procedure_steps')
                            ->label('Procedure Steps')
                            ->schema([
                                Select::make('phase')
                                    ->options(['explore' => 'Explore', 'evaluate' => 'Evaluate'])
                                    ->required(),
                                TextInput::make('title')
                                    ->required(),
                                TextInput::make('duration_minutes')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->columns(3)
                            ->reorderable()
                            ->columnSpanFull(),
                        Repeater::make('tutor_guide_glossary_terms')
                            ->label('Glossary Terms')
                            ->schema([
                                TextInput::make('term')
                                    ->required(),
                                Textarea::make('definition')
                                    ->required(),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->hiddenOn('create')
                    ->columnSpanFull(),
                Section::make('Slideshow')
                    ->description('Admin-authored, PowerPoint-style slideshow for this lesson (PDF-backed). Use the Upload/Replace Slideshow button above.')
                    ->schema([
                        Placeholder::make('slideshow_summary')
                            ->hiddenLabel()
                            ->content(function (?Lesson $record) {
                                $slideshow = $record?->slideshow;

                                if (! $slideshow) {
                                    return 'No slideshow uploaded yet.';
                                }

                                return $slideshow->isReady() ? 'Ready.' : 'Converting to PDF…';
                            }),
                    ])
                    ->hiddenOn('create')
                    ->columnSpanFull(),
            ]);
    }
}
