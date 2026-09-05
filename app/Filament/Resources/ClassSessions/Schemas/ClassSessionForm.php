<?php

namespace App\Filament\Resources\ClassSessions\Schemas;

use App\Enums\SessionStatus;
use App\Enums\SessionType;
use App\Enums\UserRole;
use App\Models\ClassSession;
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
                        // Certification is per module, not per course — a
                        // teacher only needs to have passed the quiz for
                        // the module this session's lesson belongs to.
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get): void {
                            $lesson = Lesson::with('module')->find($get('lesson_id'));
                            $module = $lesson?->module;

                            if (! $module) {
                                return;
                            }

                            $teacherProfile = User::find($value)?->teacherProfile;

                            if (! $teacherProfile || ! $teacherProfile->isCertifiedForModule($module)) {
                                $fail("This teacher hasn't passed the quiz for \"{$module->title}\" and can't be assigned to teach it yet.");
                            }
                        },
                    ]),
                Select::make('student_id')
                    ->label('Student')
                    ->options(fn () => User::role(UserRole::Student->value)->pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->required()
                    ->rules([
                        function (?ClassSession $record): Closure {
                            return function (string $attribute, $value, Closure $fail) use ($record): void {
                                $student = User::find($value);

                                if (! $student) {
                                    return;
                                }

                                // Editing an existing session for the same student doesn't
                                // consume an additional credit, so don't re-gate it here.
                                if ($record && (int) $record->student_id === (int) $value) {
                                    return;
                                }

                                if ($student->sessionCreditsBalance() <= 0) {
                                    $fail("{$student->name} has no session credits remaining. They need to purchase a session package before another session can be scheduled.");
                                }
                            };
                        },
                    ]),
                Select::make('lesson_id')
                    ->label('Lesson')
                    ->options(fn () => Lesson::with('module.course')->get()
                        ->mapWithKeys(fn (Lesson $lesson) => [
                            $lesson->id => "{$lesson->module?->course?->name} / {$lesson->module?->title} / {$lesson->title}",
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
                    ->label('Scheduled at (your local time, Europe/Dublin)')
                    ->timezone(self::ADMIN_TIMEZONE)
                    ->helperText('Enter this in Europe/Dublin time. It will be converted and shown in each teacher\'s and student\'s own timezone on their dashboards. Must be in the future, or the session won\'t appear there.')
                    ->rules(['after:now'])
                    ->validationMessages(['after' => 'The scheduled time must be in the future.'])
                    ->required(),
            ]);
    }

    /**
     * Fixed (non-reactive) timezone the admin enters scheduled_at in.
     *
     * Deliberately not tied to teacher_id/student_id via Get(): a reactive
     * timezone previously caused Filament to silently reinterpret an
     * already-typed value under a new offset when the teacher was picked
     * after the time was entered, corrupting the stored instant.
     */
    private const ADMIN_TIMEZONE = 'Europe/Dublin';
}
