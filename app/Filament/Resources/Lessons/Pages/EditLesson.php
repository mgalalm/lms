<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Enums\LessonVideoStatus;
use App\Filament\Resources\Lessons\LessonResource;
use App\Jobs\PushLessonVideoToBunnyJob;
use App\Jobs\SyncNotionContentJob;
use App\Models\LessonSlideshow;
use App\Services\Notion\NotionSyncService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Bus;
use Throwable;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    /**
     * The project's fields aren't Lesson columns (see afterSave()), so
     * Filament's normal fill-from-model doesn't populate them. Unlike the
     * tutor guide's Notion URL (deliberately left blank — paste to
     * reconnect), these should show the project's actual current values.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $project = $this->record->project;

        $data['project_title'] = $project?->title;
        $data['project_brief'] = $project?->brief;
        $data['project_is_required'] = $project?->is_required ?? false;

        $data['slideshow_google_url'] = $this->record->slideshow?->google_slides_url;

        return $data;
    }

    /**
     * Upsert the tutor guide and project alongside the Lesson save. Their
     * fields live outside $data's Lesson-column keys (Eloquent mass
     * assignment just ignores the extras), so this reads back the raw
     * submitted state via $this->data rather than the (already
     * Lesson-only) save payload.
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
            ['notion_page_id' => $pageId],
        );

        $this->record->unsetRelation('tutorGuide');

        if (filled($this->data['project_title'] ?? null)) {
            $this->record->project()->updateOrCreate([], [
                'title' => $this->data['project_title'],
                'brief' => $this->data['project_brief'] ?? null,
                'is_required' => $this->data['project_is_required'] ?? false,
            ]);
        } else {
            $this->record->project()->delete();
        }

        $this->record->unsetRelation('project');

        $slideshowUrl = $this->data['slideshow_google_url'] ?? null;

        if (filled($slideshowUrl)) {
            $this->record->slideshow()->updateOrCreate(
                ['locale' => 'en'],
                [
                    'google_slides_url' => $slideshowUrl,
                    'google_slides_id' => LessonSlideshow::extractPresentationId($slideshowUrl),
                ],
            );
        } else {
            $this->record->slideshow()->delete();
        }

        $this->record->unsetRelation('slideshow');
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
            // Grouped rather than two standalone buttons — the video/
            // delete actions already fill the header, and a labeled
            // dropdown keeps both Notion actions reachable without
            // squeezing the page title into a narrow wrapping column.
            ActionGroup::make([
                Action::make('syncFromNotion')
                    ->label('Sync from Notion')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->action(function () {
                        try {
                            // dispatchNow (not dispatchSync) so the job's
                            // handle() return value — skipped block types —
                            // actually reaches us: dispatchSync() on a
                            // ShouldQueue job routes through the queue
                            // abstraction and discards the return value.
                            $skippedBlockTypes = Bus::dispatchNow(new SyncNotionContentJob($this->record->tutorGuide));

                            if ($skippedBlockTypes === []) {
                                Notification::make()->title('Synced from Notion')->success()->send();
                            } else {
                                Notification::make()
                                    ->title('Synced from Notion (with skipped content)')
                                    ->body(
                                        "Some content types aren't supported yet and were skipped: "
                                        .implode(', ', $skippedBlockTypes).'.'
                                    )
                                    ->warning()
                                    ->send();
                            }
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
                Action::make('unlinkNotionPage')
                    ->label('Unlink Notion Page')
                    ->icon(Heroicon::OutlinedLinkSlash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('The lesson keeps its already-synced content, but will no longer be re-synced from Notion until a new page URL is linked.')
                    ->action(function () {
                        $this->record->tutorGuide?->update(['notion_page_id' => null]);

                        Notification::make()
                            ->title('Notion page unlinked')
                            ->success()
                            ->send();

                        $this->record->unsetRelation('tutorGuide');
                    }),
            ])
                ->label('Notion')
                ->icon(Heroicon::OutlinedLink)
                ->button()
                ->visible(fn () => $this->record->tutorGuide?->notion_page_id !== null),
            DeleteAction::make(),
        ];
    }
}
