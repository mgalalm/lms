<?php

namespace App\Filament\Resources\InsightMetrics;

use App\Filament\Resources\InsightMetrics\Pages\CreateInsightMetric;
use App\Filament\Resources\InsightMetrics\Pages\EditInsightMetric;
use App\Filament\Resources\InsightMetrics\Pages\ListInsightMetrics;
use App\Filament\Resources\InsightMetrics\Schemas\InsightMetricForm;
use App\Filament\Resources\InsightMetrics\Tables\InsightMetricsTable;
use App\Models\InsightMetric;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InsightMetricResource extends Resource
{
    protected static ?string $model = InsightMetric::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Insights';

    public static function form(Schema $schema): Schema
    {
        return InsightMetricForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InsightMetricsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInsightMetrics::route('/'),
            'create' => CreateInsightMetric::route('/create'),
            'edit' => EditInsightMetric::route('/{record}/edit'),
        ];
    }
}
