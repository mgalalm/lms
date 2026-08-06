<?php

namespace App\Filament\Resources\InsightMetrics\Pages;

use App\Filament\Resources\InsightMetrics\InsightMetricResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInsightMetric extends EditRecord
{
    protected static string $resource = InsightMetricResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
