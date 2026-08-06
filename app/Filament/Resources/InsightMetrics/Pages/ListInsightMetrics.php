<?php

namespace App\Filament\Resources\InsightMetrics\Pages;

use App\Filament\Resources\InsightMetrics\InsightMetricResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsightMetrics extends ListRecords
{
    protected static string $resource = InsightMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
