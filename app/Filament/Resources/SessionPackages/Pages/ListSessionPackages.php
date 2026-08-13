<?php

namespace App\Filament\Resources\SessionPackages\Pages;

use App\Filament\Resources\SessionPackages\SessionPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSessionPackages extends ListRecords
{
    protected static string $resource = SessionPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
