<?php

namespace App\Filament\Resources\StudentProfiles\Pages;

use App\Filament\Resources\StudentProfiles\StudentProfileActions;
use App\Filament\Resources\StudentProfiles\StudentProfileResource;
use Filament\Resources\Pages\EditRecord;

class EditStudentProfile extends EditRecord
{
    protected static string $resource = StudentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            StudentProfileActions::eraseAction(),
        ];
    }

    /**
     * Dot-notation fields like `user.name` only hydrate from an array key
     * path, not by traversing the Eloquent relation — so the student's
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
