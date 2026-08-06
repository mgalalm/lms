<?php

namespace App\Filament\Resources\LeaveCredits\Schemas;

use App\Enums\UserRole;
use App\Models\TeacherProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeaveCreditForm
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
                TextInput::make('monthly_limit')->numeric()->minValue(0)->default(5)->required(),
                TextInput::make('monthly_used')->numeric()->minValue(0)->default(0)->required(),
                TextInput::make('annual_limit')->numeric()->minValue(0)->default(15)->required(),
                TextInput::make('annual_used')->numeric()->minValue(0)->default(0)->required(),
            ]);
    }
}
