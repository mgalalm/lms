<?php

namespace App\Filament\Resources\SessionCreditLedgerEntries\Pages;

use App\Filament\Resources\SessionCreditLedgerEntries\SessionCreditLedgerEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSessionCreditLedgerEntry extends EditRecord
{
    protected static string $resource = SessionCreditLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
