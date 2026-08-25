<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ClassSessionRecapController extends Controller
{
    /**
     * Send the student who attended, their teacher, or an admin to this
     * session's Zoom-hosted recording. Recaps play directly from Zoom
     * rather than being downloaded and re-hosted — see
     * ZoomWebhookController::recordRecapUrl(). Every access is logged for
     * the child-safety audit trail regardless.
     */
    public function __invoke(Request $request, ClassSession $classSession): RedirectResponse
    {
        Gate::authorize('viewRecap', $classSession);

        abort_if(! $classSession->zoom_recap_url, 404);

        activity()
            ->causedBy($request->user())
            ->performedOn($classSession)
            ->withProperties(['ip' => $request->ip()])
            ->log('Viewed session recording');

        return redirect()->away($classSession->zoom_recap_url);
    }
}
