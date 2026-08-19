<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Enums\LessonVideoStatus;
use App\Filament\Resources\Lessons\LessonResource;
use App\Jobs\PushLessonVideoToBunnyJob;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

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
            DeleteAction::make(),
        ];
    }
}
