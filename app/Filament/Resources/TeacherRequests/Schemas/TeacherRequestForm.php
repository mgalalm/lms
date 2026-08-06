<?php

namespace App\Filament\Resources\TeacherRequests\Schemas;

use App\Enums\TeacherRequestStatus;
use App\Enums\TeacherRequestType;
use App\Enums\UserRole;
use App\Models\TeacherProfile;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TeacherRequestForm
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
                Select::make('type')
                    ->options(TeacherRequestType::class)
                    ->required(),
                KeyValue::make('payload')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(TeacherRequestStatus::class)
                    ->default(TeacherRequestStatus::Pending)
                    ->required(),
                Textarea::make('review_notes')
                    ->columnSpanFull(),
            ]);
    }
}
