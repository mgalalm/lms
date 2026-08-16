<?php

namespace App\Filament\Resources\TeacherProfiles\Pages;

use App\Filament\Resources\TeacherProfiles\TeacherProfileResource;
use Filament\Resources\Pages\EditRecord;

class EditTeacherProfile extends EditRecord
{
    protected static string $resource = TeacherProfileResource::class;

    /**
     * Dot-notation fields like `user.name` only hydrate from an array key
     * path, not by traversing the Eloquent relation — so the teacher's
     * name/email never appeared without being injected here explicitly.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['user'] = [
            'name' => $this->record->user->name,
            'email' => $this->record->user->email,
        ];

        return $data;
    }
}
