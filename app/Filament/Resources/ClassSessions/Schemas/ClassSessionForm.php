<?php

namespace App\Filament\Resources\ClassSessions\Schemas;

use App\Enums\SessionStatus;
use App\Enums\SessionType;
use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class ClassSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('teacher_id')
                    ->label('Teacher')
                    ->options(fn () => User::role(UserRole::Teacher->value)->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('student_id')
                    ->label('Student')
                    ->options(fn () => User::role(UserRole::Student->value)->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Select::make('lesson_id')
                    ->label('Lesson')
                    ->options(fn () => Lesson::with('module.course')->get()
                        ->mapWithKeys(fn (Lesson $lesson) => [
                            $lesson->id => "{$lesson->module?->course?->title} / {$lesson->module?->title} / {$lesson->title}",
                        ]))
                    ->searchable()
                    ->required(),
                Select::make('type')
                    ->label('Type')
                    ->options(SessionType::class)
                    ->default(SessionType::Online)
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options(SessionStatus::class)
                    ->default(SessionStatus::Scheduled)
                    ->required(),
                DateTimePicker::make('scheduled_at')
                    ->label('Scheduled at')
                    ->required(),
            ]);
    }
}
