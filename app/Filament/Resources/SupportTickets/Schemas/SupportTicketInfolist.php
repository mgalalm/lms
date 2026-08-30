<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use App\Models\Message;
use App\Models\SupportTicket;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class SupportTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('code')->label('Ticket'),
                        TextEntry::make('status')->badge()
                            ->color(fn (SupportTicket $record) => $record->status->value === 'resolved' ? 'success' : 'warning'),
                        TextEntry::make('teacher.name')->label('Teacher'),
                        TextEntry::make('classSession.lesson.title')->label('Session')->placeholder('—'),
                        TextEntry::make('subject'),
                        TextEntry::make('created_at')->dateTime(),
                    ]),

                Section::make('Conversation')
                    ->schema([
                        TextEntry::make('thread')
                            ->hiddenLabel()
                            ->state(fn (SupportTicket $record) => new HtmlString(self::renderThread($record)))
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make('Teacher Feedback')
                    ->columns(2)
                    ->visible(fn (SupportTicket $record) => $record->rating !== null)
                    ->schema([
                        TextEntry::make('rating')
                            ->label('Rating')
                            ->formatStateUsing(fn (?int $state) => $state ? str_repeat('★', $state).str_repeat('☆', 5 - $state) : '—'),
                        TextEntry::make('comment')
                            ->label('Comment')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function renderThread(SupportTicket $record): string
    {
        $messages = $record->channel->messages()->with('user')->oldest()->get();

        return $messages->map(function (Message $message) use ($record) {
            $isTeacher = $message->user_id === $record->teacher_id;

            return sprintf(
                '<div style="margin-bottom:12px;padding:10px 14px;border-radius:8px;background:%s;max-width:640px;">'
                    .'<div style="font-weight:600;font-size:13px;">%s</div>'
                    .'<div style="color:#6b7280;font-size:11px;margin-bottom:4px;">%s</div>'
                    .'<div style="font-size:13px;">%s</div>'
                    .'</div>',
                $isTeacher ? '#eff6ff' : '#f9fafb',
                e($message->user->name),
                $message->created_at->format('M j, Y g:i A'),
                $message->body ?? ''
            );
        })->implode('');
    }
}
