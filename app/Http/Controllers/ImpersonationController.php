<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets an admin log in as a teacher or student — for support/debugging,
 * never available between two admins. Every start/stop is logged to the
 * activity trail, same as this codebase's other sensitive-access logging
 * (viewing a student's chat history, session recordings, etc.).
 */
class ImpersonationController extends Controller
{
    /**
     * Start impersonating $user. Only reachable by an admin (route
     * middleware), and only targets a non-admin.
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        abort_if($user->hasRole(UserRole::Admin->value), 403, 'Cannot impersonate another admin.');

        $admin = $request->user();

        session(['impersonator_id' => $admin->id]);

        activity()
            ->causedBy($admin)
            ->performedOn($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Started impersonating');

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    /**
     * Stop impersonating and return to the original admin session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        abort_unless($impersonatorId, 404);

        $impersonator = User::findOrFail($impersonatorId);
        $impersonated = $request->user();

        $request->session()->forget('impersonator_id');

        activity()
            ->causedBy($impersonator)
            ->performedOn($impersonated)
            ->withProperties(['ip' => $request->ip()])
            ->log('Stopped impersonating');

        Auth::login($impersonator);

        return redirect()->route('filament.admin.pages.dashboard');
    }
}
