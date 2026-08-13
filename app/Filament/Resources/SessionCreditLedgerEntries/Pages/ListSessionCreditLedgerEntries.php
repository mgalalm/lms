<?php

namespace App\Filament\Resources\SessionCreditLedgerEntries\Pages;

use App\Filament\Resources\SessionCreditLedgerEntries\SessionCreditLedgerEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSessionCreditLedgerEntries extends ListRecords
{
    protected static string $resource = SessionCreditLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
