<?php

namespace App\Filament\Resources\ClassSessions\Tables;

use App\Enums\SessionStatus;
use App\Enums\SessionType;
use App\Models\TeacherProfile;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClassSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('teacher.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('student.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('lesson.title')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (SessionStatus $state) => match ($state) {
                        SessionStatus::Scheduled => 'gray',
                        SessionStatus::InProgress => 'warning',
                        SessionStatus::Completed => 'success',
                        SessionStatus::Cancelled => 'danger',
                    }),
                TextColumn::make('scheduled_at')
                    ->dateTime(timezone: fn ($record) => $record->teacher?->teacherProfile?->resolvedTimezone() ?? TeacherProfile::DEFAULT_TIMEZONE)
                    ->sortable(),
                IconColumn::make('zoom_meeting_id')
                    ->label('Zoom Ready')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->type === SessionType::Online ? filled($record->zoom_meeting_id) : null),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SessionStatus::class),
                SelectFilter::make('type')
                    ->options(SessionType::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
