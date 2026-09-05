<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Enums\LessonQuizQuestionType;
use App\Models\Lesson;
use App\Models\LessonSlideshow;
use App\Services\Bunny\BunnyStreamClient;
use App\Services\Notion\NotionSyncService;
use App\Services\QuizGenerationService;
use Closure;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->description('Google Slides presentation for this lesson, embedded for teachers in the LMS view.')
                    ->schema([
                        TextInput::make('slideshow_google_url')
                            ->label('Google Slides URL')
                            ->url()
                            ->live(onBlur: true)
                            ->helperText('Paste a docs.google.com/presentation link. Set sharing to "Anyone with the link can view" in Google Slides, otherwise it will not display for teachers.')
                            ->rule(function () {
                                return function (string $attribute, $value, Closure $fail) {
                                    if (blank($value)) {
                                        return;
                                    }

                                    if (! LessonSlideshow::extractPresentationId($value)) {
                                        $fail('That doesn\'t look like a valid Google Slides presentation URL.');
                                    }
                                };
                            })
                            ->columnSpanFull(),
                        Placeholder::make('slideshow_preview')
                            ->hiddenLabel()
                            ->content(function (Get $get) {
                                $id = LessonSlideshow::extractPresentationId($get('slideshow_google_url'));

                                if (! $id) {
                                    return null;
                                }

                                $url = "https://docs.google.com/presentation/d/{$id}/embed";

                                return new HtmlString(
                                    '<iframe src="'.e($url).'" class="aspect-video w-full max-w-xl rounded-lg" allowfullscreen></iframe>'
                                );
                            })
                            ->columnSpanFull(),
                        Textarea::make('slides_text')
                            ->label('Slide Text (admin only)')
                            ->rows(12)
                            ->helperText(
                                'Pasted slide content used to generate this lesson\'s quiz — never shown to '
                                .'students or teachers. Separate slides with a blank line or a "---" line so '
                                .'question generation has some sense of slide boundaries.'
                            )
                            ->columnSpanFull(),
                    ])
                    ->hiddenOn('create')
                    ->columnSpanFull(),
                Section::make('Quiz')
                    ->description('Student-facing quiz generated from the slide text above via OpenAI. Fully editable — rewrite, add, remove, or reorder questions as needed.')
                    ->schema([
                        Placeholder::make('quiz_status')
                            ->hiddenLabel()
                            ->content(function (?Lesson $record) {
                                $quiz = $record?->quiz;

                                if (! $quiz) {
                                    return 'No quiz generated yet.';
                                }

                                $summary = 'Status: '.$quiz->status->getLabel();

                                if ($quiz->generated_at) {
                                    $summary .= ' · Generated '.$quiz->generated_at->diffForHumans();
                                }

                                $currentHash = app(QuizGenerationService::class)->sourceHash($record);

                                if ($quiz->source_text_hash && $quiz->source_text_hash !== $currentHash) {
                                    return new HtmlString(
                                        e($summary).'<br><span class="font-medium text-warning-600">⚠ The lesson content has changed since this quiz was generated.</span>'
                                    );
                                }

                                return $summary;
                            }),
                        Repeater::make('quiz_questions')
                            ->hiddenLabel()
                            ->reorderable()
                            ->reorderableWithDragAndDrop()
                            ->addActionLabel('Add Question')
                            ->itemLabel(fn (array $state) => (($state['is_ai_generated'] ?? false) ? '🤖 AI · ' : '').($state['question_text'] ?? 'New question'))
                            ->schema([
                                Textarea::make('question_text')
                                    ->label('Question')
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('type')
                                    ->options(LessonQuizQuestionType::class)
                                    ->default(LessonQuizQuestionType::MultipleChoice->value)
                                    ->live()
                                    ->required(),
                                Toggle::make('is_ai_generated')
                                    ->label('AI-generated (uncheck once reviewed)')
                                    ->inline(false),
                                TagsInput::make('options')
                                    ->label('Options')
                                    ->helperText('Press enter after each option.')
                                    ->live()
                                    ->required()
                                    ->columnSpanFull(),
                                Select::make('correct_answer')
                                    ->label('Correct Answer')
                                    ->options(fn (Get $get) => collect($get('options') ?? [])->mapWithKeys(fn ($option) => [$option => $option]))
                                    ->required()
                                    ->columnSpanFull(),
                                Textarea::make('explanation')
                                    ->label('Explanation')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->hiddenOn('create')
                    ->columnSpanFull(),
            ]);
    }
}
