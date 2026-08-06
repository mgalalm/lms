<?php

namespace App\Filament\Resources\PayrollEntries\Pages;

use App\Filament\Resources\PayrollEntries\PayrollEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayrollEntries extends ListRecords
{
    protected static string $resource = PayrollEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
