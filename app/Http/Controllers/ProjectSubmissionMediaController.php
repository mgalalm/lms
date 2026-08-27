<?php

namespace App\Http\Controllers;

use App\Models\ProjectSubmission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a project submission's screenshot privately — Media::getUrl()
 * doesn't resolve to a working URL on the 'bunny' disk in this app (no
 * public CDN hostname configured for it), same reason
 * LessonTutorGuideMediaController exists instead of linking directly.
 */
class ProjectSubmissionMediaController extends Controller
{
    public function __invoke(ProjectSubmission $projectSubmission, Media $media): StreamedResponse
    {
        Gate::authorize('view', $projectSubmission);

        abort_unless(
            $media->model_type === $projectSubmission->getMorphClass()
            && $media->model_id === $projectSubmission->getKey()
            && $media->collection_name === 'screenshots',
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
