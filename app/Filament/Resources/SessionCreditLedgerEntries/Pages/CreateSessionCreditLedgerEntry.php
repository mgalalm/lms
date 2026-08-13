<?php

namespace App\Filament\Resources\SessionCreditLedgerEntries\Pages;

use App\Filament\Resources\SessionCreditLedgerEntries\SessionCreditLedgerEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSessionCreditLedgerEntry extends CreateRecord
{
    protected static string $resource = SessionCreditLedgerEntryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
