<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Enums\LessonVideoStatus;
use App\Filament\Resources\Lessons\LessonResource;
use App\Jobs\ConvertLessonSlideshowToPdfJob;
use App\Jobs\PushLessonSlideshowToBunnyJob;
use App\Jobs\PushLessonVideoToBunnyJob;
use App\Jobs\SyncNotionContentJob;
use App\Services\Notion\NotionSyncService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Throwable;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    /**
     * The tutor guide's fields aren't Lesson columns (see afterSave()), so
     * Filament's normal fill-from-model doesn't populate them.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $guide = $this->record->tutorGuide;

        // tutor_guide_notion_url is intentionally left blank on fill — it's
        // a "paste to (re)connect" field, not a display of the stored page
        // ID (the original URL isn't retained). Current link status is
        // shown separately via the tutor_guide_notion_status Placeholder.
        $data['tutor_guide_procedure_steps'] = $guide?->procedure_steps ?? [];
        $data['tutor_guide_glossary_terms'] = $guide?->glossary_terms ?? [];

        return $data;
    }

    /**
     * Upsert the tutor guide alongside the Lesson save. Its fields live
     * outside $data's Lesson-column keys (Eloquent mass assignment just
     * ignores the extras), so this reads back the raw submitted state via
     * $this->data rather than the (already Lesson-only) save payload.
     */
    protected function afterSave(): void
    {
        $guide = $this->record->tutorGuide;
        $notionUrl = $this->data['tutor_guide_notion_url'] ?? null;

        $pageId = filled($notionUrl)
            ? app(NotionSyncService::class)->extractPageId($notionUrl)
            : $guide?->notion_page_id;

        $this->record->tutorGuide()->updateOrCreate(
            ['locale' => 'en'],
            [
                'notion_page_id' => $pageId,
                'procedure_steps' => $this->data['tutor_guide_procedure_steps'] ?? [],
                'glossary_terms' => $this->data['tutor_guide_glossary_terms'] ?? [],
            ],
        );

        $this->record->unsetRelation('tutorGuide');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadVideo')
                ->label(fn () => $this->record->video ? 'Replace Video' : 'Upload Video')
                ->icon(Heroicon::OutlinedVideoCamera)
                ->schema([
                    FileUpload::make('file')
                        ->label('Video File')
                        ->disk('local')
                        ->directory('lesson-video-uploads')
                        ->visibility('private')
                        ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm'])
                        ->maxSize(512000)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $lessonVideo = $this->record->video()->updateOrCreate(
                        ['locale' => 'en'],
                        [
                            'status' => LessonVideoStatus::Uploading,
                            'uploaded_by' => auth()->id(),
                            'bunny_video_id' => null,
                            'duration_seconds' => null,
                            'thumbnail_url' => null,
                            'published_at' => null,
                        ],
                    );

                    PushLessonVideoToBunnyJob::dispatch($lessonVideo, 'local', $data['file']);

                    $this->record->unsetRelation('video');
                }),
            Action::make('publishVideo')
                ->label('Publish Video')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn () => $this->record->video !== null)
                ->disabled(fn () => $this->record->video?->status !== LessonVideoStatus::Draft)
                ->requiresConfirmation()
                ->action(fn () => $this->record->video->publish()),
            Action::make('uploadSlideshow')
                ->label(fn () => $this->record->slideshow ? 'Replace Slideshow' : 'Upload Slideshow')
                ->icon(Heroicon::OutlinedPresentationChartBar)
                ->schema([
                    FileUpload::make('file')
                        ->label('Slideshow File (PPTX or PDF)')
                        ->disk('local')
                        ->directory('lesson-slideshow-uploads')
                        ->visibility('private')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        ])
                        ->maxSize(512000)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $slideshow = $this->record->slideshow()->updateOrCreate(
                        ['locale' => 'en'],
                        ['file_path' => null],
                    );

                    if (strtolower(pathinfo($data['file'], PATHINFO_EXTENSION)) === 'pdf') {
                        PushLessonSlideshowToBunnyJob::dispatch($slideshow, 'local', $data['file']);
                    } else {
                        ConvertLessonSlideshowToPdfJob::dispatch($slideshow, 'local', $data['file']);
                    }

                    $this->record->unsetRelation('slideshow');
                }),
            Action::make('syncFromNotion')
                ->label('Sync from Notion')
                ->icon(Heroicon::OutlinedArrowPath)
                ->visible(fn () => $this->record->tutorGuide?->notion_page_id !== null)
                ->action(function () {
                    try {
                        SyncNotionContentJob::dispatchSync($this->record->tutorGuide);

                        Notification::make()
                            ->title('Synced from Notion')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Notion sync failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }

                    $this->record->unsetRelation('tutorGuide');
                }),
            DeleteAction::make(),
        ];
    }
}
