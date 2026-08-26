<?php

namespace App\Filament\Resources\FeedbackTemplates\Pages;

use App\Filament\Resources\FeedbackTemplates\FeedbackTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeedbackTemplates extends ListRecords
{
    protected static string $resource = FeedbackTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
