<?php

namespace App\Filament\Resources\PointsLedgerEntries\Pages;

use App\Filament\Resources\PointsLedgerEntries\PointsLedgerEntryResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePointsLedgerEntry extends CreateRecord
{
    protected static string $resource = PointsLedgerEntryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['awarded_by'] = auth()->id();

        return $data;
    }
}
