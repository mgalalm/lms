<?php

namespace App\Filament\Resources\FeedbackTemplates\Pages;

use App\Filament\Resources\FeedbackTemplates\FeedbackTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeedbackTemplate extends EditRecord
{
    protected static string $resource = FeedbackTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
