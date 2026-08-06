<?php

namespace App\Filament\Resources\SessionReviews\Pages;

use App\Filament\Resources\SessionReviews\SessionReviewResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSessionReview extends CreateRecord
{
    protected static string $resource = SessionReviewResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reviewed_by'] = auth()->id();

        return $data;
    }
}
