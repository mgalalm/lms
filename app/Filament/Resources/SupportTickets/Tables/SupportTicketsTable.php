<?php

namespace App\Filament\Resources\SupportTickets\Tables;

use App\Enums\SupportTicketStatus;
use App\Filament\Resources\SupportTickets\SupportTicketActions;
use App\Models\SupportTicket;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('subject')
                    ->searchable(),
                TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('classSession.lesson.title')
                    ->label('Session')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (SupportTicketStatus $state) => match ($state) {
                        SupportTicketStatus::Open => 'warning',
                        SupportTicketStatus::Resolved => 'success',
                    }),
                TextColumn::make('created_at')
                    ->label('Opened')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SupportTicketStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('reply')
                    ->color('primary')
                    ->schema([
                        Textarea::make('body')
                            ->label('Reply')
                            ->required(),
                    ])
                    ->action(function (SupportTicket $record, array $data) {
                        SupportTicketActions::reply($record, $data['body']);

                        Notification::make()->title('Reply sent')->success()->send();
                    }),
                Action::make('toggleStatus')
                    ->label(fn (SupportTicket $record) => $record->status === SupportTicketStatus::Open ? 'Resolve' : 'Reopen')
                    ->color(fn (SupportTicket $record) => $record->status === SupportTicketStatus::Open ? 'success' : 'gray')
                    ->requiresConfirmation()
                    ->action(function (SupportTicket $record) {
                        SupportTicketActions::toggleStatus($record);

                        Notification::make()->title('Ticket updated')->success()->send();
                    }),
            ]);
    }
}
