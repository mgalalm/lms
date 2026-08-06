<?php

namespace App\Filament\Resources\SessionReviews\Pages;

use App\Filament\Resources\SessionReviews\SessionReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSessionReview extends EditRecord
{
    protected static string $resource = SessionReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
