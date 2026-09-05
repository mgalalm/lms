<?php

namespace App\Filament\Resources\Modules\RelationManagers;

use App\Models\TeacherModuleQuizAttempt;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Teacher attempts at this module's certification quiz, and the admin's
 * escape hatch: a teacher who exhausts their attempts without passing
 * stays uncertified for the module — and so can't be assigned to teach
 * its lessons — until an admin resets them here.
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
            ->emptyStateHeading('No teacher has attempted this module quiz yet')
            ->columns([
                TextColumn::make('teacherProfile.user.email')
                    ->label('Teacher')
                    ->searchable(),
                TextColumn::make('score')
                    ->suffix('%')
                    ->placeholder('In progress'),
                IconColumn::make('passed')
                    ->boolean()
                    ->placeholder('—'),
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->placeholder('Not submitted'),
                TextColumn::make('voided_at')
                    ->label('Reset')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('resetAttempts')
                    ->label('Reset attempts')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->visible(fn (TeacherModuleQuizAttempt $record) => $record->voided_at === null)
                    ->requiresConfirmation()
                    ->modalDescription('Voids ALL of this teacher\'s attempts at this module quiz, giving them a full set of attempts again. Their attempt history is kept for the record.')
                    ->action(function (TeacherModuleQuizAttempt $record) {
                        $voided = TeacherModuleQuizAttempt::query()
                            ->where('teacher_profile_id', $record->teacher_profile_id)
                            ->where('module_id', $record->module_id)
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
