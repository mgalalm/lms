<?php

namespace App\Filament\Resources\SessionReviews\Pages;

use App\Filament\Resources\SessionReviews\SessionReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSessionReviews extends ListRecords
{
    protected static string $resource = SessionReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
