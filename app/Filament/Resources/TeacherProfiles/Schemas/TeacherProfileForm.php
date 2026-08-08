<?php

namespace App\Filament\Resources\TeacherProfiles\Schemas;

use App\Models\TeacherProfile;
use DateTimeZone;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class TeacherProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user.name')
                    ->label('Teacher')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('code')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('timezone')
                    ->label('Timezone')
                    ->options(fn (): Collection => collect(DateTimeZone::listIdentifiers())->mapWithKeys(fn (string $tz) => [$tz => $tz]))
                    ->searchable()
                    ->native(false)
                    ->placeholder(TeacherProfile::DEFAULT_TIMEZONE.' (default)')
                    ->helperText('Used to interpret and display this teacher\'s session times. Defaults to '.TeacherProfile::DEFAULT_TIMEZONE.' if left blank.'),
            ]);
    }
}
