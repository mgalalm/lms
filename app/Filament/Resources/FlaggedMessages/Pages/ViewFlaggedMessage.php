<?php

namespace App\Filament\Resources\FlaggedMessages\Pages;

use App\Enums\MessageModerationStatus;
use App\Filament\Resources\FlaggedMessages\FlaggedMessageActions;
use App\Filament\Resources\FlaggedMessages\FlaggedMessageResource;
use App\Models\Message;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewFlaggedMessage extends ViewRecord
{
    protected static string $resource = FlaggedMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (Message $record) => $record->moderation_status !== MessageModerationStatus::Approved)
                ->requiresConfirmation()
                ->action(function (Message $record) {
                    FlaggedMessageActions::approve($record);
                    $this->redirect(FlaggedMessageResource::getUrl('index'));
                }),
        ];
    }
}
