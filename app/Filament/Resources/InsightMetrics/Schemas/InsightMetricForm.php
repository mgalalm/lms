<?php

namespace App\Filament\Resources\InsightMetrics\Schemas;

use App\Enums\UserRole;
use App\Models\TeacherProfile;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InsightMetricForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('teacher_profile_id')
                    ->label('Teacher')
                    ->options(fn () => TeacherProfile::whereHas('user', fn ($query) => $query->role(UserRole::Teacher->value))
                        ->with('user')
                        ->get()
                        ->mapWithKeys(fn (TeacherProfile $profile) => [$profile->id => $profile->user->name]))
                    ->searchable()
                    ->required(),
                DatePicker::make('period_start')
                    ->required(),
                DatePicker::make('period_end')
                    ->required(),
                TextInput::make('objections')->numeric()->minValue(0)->default(0)->required(),
                TextInput::make('excuses')->numeric()->minValue(0)->default(0)->required(),
                TextInput::make('study_level')->numeric()->minValue(0)->default(0)->required(),
            ]);
    }
}
