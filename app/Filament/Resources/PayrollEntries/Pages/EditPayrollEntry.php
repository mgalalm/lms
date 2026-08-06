<?php

namespace App\Filament\Resources\PayrollEntries\Pages;

use App\Filament\Resources\PayrollEntries\PayrollEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayrollEntry extends EditRecord
{
    protected static string $resource = PayrollEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
