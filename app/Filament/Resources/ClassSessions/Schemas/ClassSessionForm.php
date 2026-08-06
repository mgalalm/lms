<?php

namespace App\Filament\Resources\ClassSessions\Schemas;

use App\Enums\SessionStatus;
use App\Enums\SessionType;
use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\User;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->live()
                    ->required()
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                            $lesson = Lesson::with('module.course')->find($get('lesson_id'));
                            $course = $lesson?->module?->course;

                            if (! $course) {
                                return;
                            }

                            $teacherProfile = User::find($value)?->teacherProfile;

                            if (! $teacherProfile || ! $teacherProfile->isCertifiedForCourse($course)) {
                                $fail("This teacher hasn't completed \"{$course->title}\" (including its quiz) and can't be assigned to teach it yet.");
                            }
                        },
                    ]),
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
                    ->live()
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
