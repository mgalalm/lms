<?php

namespace App\Filament\Resources\SessionReviews\Schemas;

use App\Enums\FlagSeverity;
use App\Models\ClassSession;
use App\Models\TeacherProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SessionReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('class_session_id')
                    ->label('Session')
                    ->options(fn () => ClassSession::with(['teacher.teacherProfile', 'student', 'lesson'])->get()
                        ->mapWithKeys(fn (ClassSession $session) => [
                            $session->id => "{$session->teacher?->name} / {$session->student?->name} / {$session->lesson?->title} ({$session->scheduled_at->copy()->setTimezone($session->teacher?->teacherProfile?->resolvedTimezone() ?? TeacherProfile::DEFAULT_TIMEZONE)->format('M j, Y')})",
                        ]))
                    ->searchable()
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('star_rating')
                    ->label('Star rating')
                    ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                    ->default(3)
                    ->required(),
                Select::make('flag_severity')
                    ->label('Flag')
                    ->options(FlagSeverity::class)
                    ->default(FlagSeverity::Green)
                    ->required(),
                TextInput::make('feedback_score')->numeric()->minValue(0)->maxValue(100)->default(0)->required(),
                TextInput::make('attitude_score')->numeric()->minValue(0)->maxValue(100)->default(0)->required(),
                TextInput::make('preparation_score')->numeric()->minValue(0)->maxValue(100)->default(0)->required(),
                TextInput::make('curriculum_score')->numeric()->minValue(0)->maxValue(100)->default(0)->required(),
                TextInput::make('teaching_score')->numeric()->minValue(0)->maxValue(100)->default(0)->required(),
                TextInput::make('setup_score')->numeric()->minValue(0)->maxValue(100)->default(0)->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
