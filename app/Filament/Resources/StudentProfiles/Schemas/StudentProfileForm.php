<?php

namespace App\Filament\Resources\StudentProfiles\Schemas;

use App\Models\StudentProfile;
use DateTimeZone;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class StudentProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user.name')
                    ->label('Student')
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
                    ->placeholder(StudentProfile::DEFAULT_TIMEZONE.' (default)')
                    ->helperText('Used to display this student\'s session times. Defaults to '.StudentProfile::DEFAULT_TIMEZONE.' if left blank.'),
            ]);
    }
}
