<?php

namespace App\Filament\Resources\PayrollEntries\Schemas;

use App\Enums\PayrollStatus;
use App\Enums\UserRole;
use App\Models\TeacherProfile;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PayrollEntryForm
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
                DatePicker::make('month')
                    ->required(),
                TextInput::make('organization')
                    ->default('iSchool')
                    ->required(),
                TextInput::make('sessions_count')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                TextInput::make('basic_salary')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->prefix('EGP'),
                Select::make('status')
                    ->options(PayrollStatus::class)
                    ->default(PayrollStatus::Pending)
                    ->required(),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
