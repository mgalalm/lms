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

    /**
     * Part of the child-safety audit trail: every time this review page is
     * opened, not just when a moderation decision is made.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->getRecord())
            ->withProperties(['ip' => request()->ip()])
            ->log('Reviewed flagged message');
    }

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
