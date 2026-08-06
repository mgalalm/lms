<?php

namespace App\Filament\Resources\PayrollEntries\Pages;

use App\Filament\Resources\PayrollEntries\PayrollEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollEntry extends CreateRecord
{
    protected static string $resource = PayrollEntryResource::class;
}
