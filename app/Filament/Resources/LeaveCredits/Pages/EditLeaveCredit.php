<?php

namespace App\Filament\Resources\LeaveCredits\Pages;

use App\Filament\Resources\LeaveCredits\LeaveCreditResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLeaveCredit extends EditRecord
{
    protected static string $resource = LeaveCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
