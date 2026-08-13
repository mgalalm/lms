<?php

namespace App\Filament\Resources\SessionPackages\Pages;

use App\Filament\Resources\SessionPackages\SessionPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSessionPackage extends EditRecord
{
    protected static string $resource = SessionPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
