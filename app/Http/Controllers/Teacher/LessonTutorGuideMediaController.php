<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\LessonTutorGuide;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LessonTutorGuideMediaController extends Controller
{
    public function __invoke(LessonTutorGuide $lessonTutorGuide, Media $media): StreamedResponse
    {
        Gate::authorize('view', $lessonTutorGuide);

        abort_unless(
            $media->model_type === $lessonTutorGuide->getMorphClass()
            && $media->model_id === $lessonTutorGuide->getKey()
            && $media->collection_name === 'intro-images',
            404,
        );

        return Storage::disk($media->disk)->response(
            $media->getPathRelativeToRoot(),
            $media->file_name,
            [
                'Content-Type' => $media->mime_type,
                'Cache-Control' => 'private, max-age=3600',
            ],
        );
    }
}
