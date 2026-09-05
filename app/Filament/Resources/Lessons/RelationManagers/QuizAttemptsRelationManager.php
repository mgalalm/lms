<?php

namespace App\Filament\Resources\Lessons\RelationManagers;

use App\Models\TeacherLessonQuizAttempt;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Teacher attempts at this lesson's quiz, and the admin's escape hatch:
 * a teacher who exhausts their attempts without passing can't complete
 * the lesson — and so can't certify for the course, or be assigned to
 * teach it — until an admin resets them here.
 */
class QuizAttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'quizAttempts';

    protected static ?string $title = 'Quiz Attempts';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No teacher has attempted this quiz yet')
            ->columns([
                TextColumn::make('teacherProfile.user.email')
                    ->label('Teacher')
                    ->searchable(),
                TextColumn::make('score')
                    ->suffix('%'),
                IconColumn::make('passed')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Attempted')
                    ->dateTime(),
                TextColumn::make('voided_at')
                    ->label('Reset')
                    ->dateTime()
                    ->placeholder('—')
                    ->description('Reset attempts no longer count toward the cap.'),
            ])
            ->recordActions([
                Action::make('resetAttempts')
                    ->label('Reset attempts')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->visible(fn (TeacherLessonQuizAttempt $record) => $record->voided_at === null)
                    ->requiresConfirmation()
                    ->modalDescription('Voids ALL of this teacher\'s attempts at this quiz, giving them a full set of attempts again. Their attempt history is kept for the record.')
                    ->action(function (TeacherLessonQuizAttempt $record) {
                        $voided = TeacherLessonQuizAttempt::query()
                            ->where('teacher_profile_id', $record->teacher_profile_id)
                            ->where('lesson_quiz_id', $record->lesson_quiz_id)
                            ->whereNull('voided_at')
                            ->update(['voided_at' => now()]);

                        Notification::make()
                            ->title("Reset {$voided} attempt(s)")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
