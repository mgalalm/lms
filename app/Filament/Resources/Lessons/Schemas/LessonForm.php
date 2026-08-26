<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Models\Lesson;
use App\Services\Bunny\BunnyStreamClient;
use App\Services\Notion\NotionSyncService;
use Closure;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                    ->description('Teacher/admin prep material for this lesson — never shown to students, under any circumstance. Authored in Notion, synced into our own storage — content never links out to Notion.')
                    ->schema([
                        Placeholder::make('tutor_guide_notion_status')
                            ->label('Notion Sync Status')
                            ->content(function (?Lesson $record) {
                                $guide = $record?->tutorGuide;

                                if (! $guide?->notion_page_id) {
                                    return 'Not linked to a Notion page yet.';
                                }

                                $syncedAt = $guide->last_synced_at?->diffForHumans() ?? 'never';
                                $text = "Linked to Notion page {$guide->notion_page_id}. Last synced: {$syncedAt}.";

                                if ($guide->isSyncStale()) {
                                    return new HtmlString('<span class="font-medium text-danger-600">⚠ '.e($text).' Sync is more than 7 days old.</span>');
                                }

                                return $text;
                            })
                            ->columnSpanFull(),
                        TextInput::make('tutor_guide_notion_url')
                            ->label('Notion Page URL')
                            ->url()
                            ->helperText('Paste an app.notion.com, notion.so, or notion.site page URL to (re)connect. Leave blank to keep the current link. Use "Sync from Notion" above to pull in content.')
                            ->rule(function () {
                                return function (string $attribute, $value, Closure $fail) {
                                    if (blank($value)) {
                                        return;
                                    }

                                    $host = parse_url($value, PHP_URL_HOST);

                                    if (! $host || ! preg_match('/(^|\.)notion\.(com|so|site)$/', $host)) {
                                        $fail('The Notion Page URL must be an app.notion.com, notion.so, or notion.site link.');

                                        return;
                                    }

                                    if (! app(NotionSyncService::class)->extractPageId($value)) {
                                        $fail('Could not find a Notion page ID in that URL.');
                                    }
                                };
                            })
                            ->columnSpanFull(),
                    ])
                    ->hiddenOn('create')
                    ->columnSpanFull(),
                Section::make('Project')
                    ->description('Optional hands-on project assignment for this lesson — students submit their work and teachers review it with structured feedback.')
                    ->schema([
                        TextInput::make('project_title')
                            ->label('Project Title')
                            ->maxLength(255),
                        Textarea::make('project_brief')
                            ->label('Assignment Brief')
                            ->helperText('Shown to the student alongside the submission form.')
                            ->columnSpanFull(),
                        Toggle::make('project_is_required')
                            ->label('Required to complete the lesson'),
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
