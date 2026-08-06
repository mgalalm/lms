<?php

namespace App\Filament\Resources\LeaveCredits\Pages;

use App\Filament\Resources\LeaveCredits\LeaveCreditResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeaveCredits extends ListRecords
{
    protected static string $resource = LeaveCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
