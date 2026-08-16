<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Models\User;
use App\Rules\Turnstile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'parent_email' => ['required', 'string', 'email', 'max:255'],
            'consent' => ['required', 'accepted'],
            // Not marked 'required' — the Turnstile rule itself only
            // enforces a token when TURNSTILE_SECRET_KEY is configured,
            // so local/test environments without Cloudflare keys aren't
            // blocked from registering.
            'turnstile_token' => ['string', new Turnstile],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Public registration is for students only; teacher accounts are
        // provisioned by an admin after certification/vetting.
        $user->assignRole(UserRole::Student->value);
        StudentProfile::create([
            'user_id' => $user->id,
            'code' => 'S-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
            'parent_email' => $request->parent_email,
            'consent_given_at' => now(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
