<?php

namespace App\Filament\Resources\FeedbackTemplates;

use App\Filament\Resources\FeedbackTemplates\Pages\CreateFeedbackTemplate;
use App\Filament\Resources\FeedbackTemplates\Pages\EditFeedbackTemplate;
use App\Filament\Resources\FeedbackTemplates\Pages\ListFeedbackTemplates;
use App\Filament\Resources\FeedbackTemplates\Schemas\FeedbackTemplateForm;
use App\Filament\Resources\FeedbackTemplates\Tables\FeedbackTemplatesTable;
use App\Models\FeedbackTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FeedbackTemplateResource extends Resource
{
    protected static ?string $model = FeedbackTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Curriculum';

    protected static ?int $navigationSort = 33;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return FeedbackTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeedbackTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedbackTemplates::route('/'),
            'create' => CreateFeedbackTemplate::route('/create'),
            'edit' => EditFeedbackTemplate::route('/{record}/edit'),
        ];
    }
}
