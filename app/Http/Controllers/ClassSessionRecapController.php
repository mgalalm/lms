<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassSessionRecapController extends Controller
{
    /**
     * Stream this session's recorded video inline for playback (not a
     * forced download) — the student who attended, their teacher, or an
     * admin. Every access is logged for the child-safety audit trail.
     */
    public function __invoke(Request $request, ClassSession $classSession): StreamedResponse
    {
        Gate::authorize('viewRecap', $classSession);

        abort_if(! $classSession->recap_path, 404);

        activity()
            ->causedBy($request->user())
            ->performedOn($classSession)
            ->withProperties(['ip' => $request->ip()])
            ->log('Viewed session recording');

        return Storage::disk('local')->response($classSession->recap_path);
    }
}
