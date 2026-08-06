<?php

namespace App\Filament\Resources\PointsLedgerEntries\Pages;

use App\Filament\Resources\PointsLedgerEntries\PointsLedgerEntryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPointsLedgerEntry extends EditRecord
{
    protected static string $resource = PointsLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
