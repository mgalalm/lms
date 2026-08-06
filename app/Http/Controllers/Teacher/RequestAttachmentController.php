<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\TeacherRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequestAttachmentController extends Controller
{
    /**
     * Stream a request's attachment — only to the teacher who submitted it
     * or an admin. Stored on the private `local` disk since these can be
     * sensitive HR documents (sick notes, etc.), not the public disk.
     */
    public function __invoke(Request $request, TeacherRequest $teacherRequest): StreamedResponse
    {
        abort_unless($teacherRequest->attachment_path, 404);

        $isOwner = $teacherRequest->teacherProfile->user_id === $request->user()->id;
        $isAdmin = $request->user()->hasRole(UserRole::Admin->value);

        abort_unless($isOwner || $isAdmin, 403);

        return Storage::disk('local')->download($teacherRequest->attachment_path);
    }
}
