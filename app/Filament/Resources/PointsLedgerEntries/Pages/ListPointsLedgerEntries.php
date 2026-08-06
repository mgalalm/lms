<?php

namespace App\Filament\Resources\PointsLedgerEntries\Pages;

use App\Filament\Resources\PointsLedgerEntries\PointsLedgerEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPointsLedgerEntries extends ListRecords
{
    protected static string $resource = PointsLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
