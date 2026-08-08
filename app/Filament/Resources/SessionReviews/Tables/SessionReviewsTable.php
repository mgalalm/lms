<?php

namespace App\Filament\Resources\SessionReviews\Tables;

use App\Enums\FlagSeverity;
use App\Models\SessionReview;
use App\Models\TeacherProfile;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SessionReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('classSession.teacher.name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('classSession.student.name')
                    ->label('Student')
                    ->searchable(),
                TextColumn::make('classSession.lesson.title')
                    ->label('Lesson'),
                TextColumn::make('classSession.scheduled_at')
                    ->label('Session date')
                    ->dateTime(timezone: fn ($record) => $record->classSession?->teacher?->teacherProfile?->resolvedTimezone() ?? TeacherProfile::DEFAULT_TIMEZONE)
                    ->sortable(),
                TextColumn::make('flag_severity')
                    ->label('Flag')
                    ->badge()
                    ->color(fn (FlagSeverity $state) => match ($state) {
                        FlagSeverity::Green => 'success',
                        FlagSeverity::Yellow => 'warning',
                        FlagSeverity::Red => 'danger',
                    }),
                TextColumn::make('average_score')
                    ->label('Total Score')
                    ->state(fn (SessionReview $record) => "{$record->averageScore()}%"),
            ])
            ->filters([
                SelectFilter::make('flag_severity')
                    ->options(FlagSeverity::class),
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
