<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Enums\SupportTicketStatus;
use App\Filament\Resources\SupportTickets\SupportTicketActions;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\SupportTicket;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->color('primary')
                ->schema([
                    Textarea::make('body')
                        ->label('Reply')
                        ->required(),
                ])
                ->action(function (array $data) {
                    /** @var SupportTicket $ticket */
                    $ticket = $this->getRecord();

                    SupportTicketActions::reply($ticket, $data['body']);

                    Notification::make()->title('Reply sent')->success()->send();
                }),
            Action::make('toggleStatus')
                ->label(fn () => $this->getRecord()->status === SupportTicketStatus::Open ? 'Resolve' : 'Reopen')
                ->color(fn () => $this->getRecord()->status === SupportTicketStatus::Open ? 'success' : 'gray')
                ->requiresConfirmation()
                ->action(function () {
                    /** @var SupportTicket $ticket */
                    $ticket = $this->getRecord();

                    SupportTicketActions::toggleStatus($ticket);

                    Notification::make()->title('Ticket updated')->success()->send();
                }),
        ];
    }
}
