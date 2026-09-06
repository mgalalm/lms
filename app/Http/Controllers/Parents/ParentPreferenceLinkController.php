<?php

namespace App\Http\Controllers\Parents;

use App\Http\Controllers\Controller;
use App\Models\ParentContact;
use App\Notifications\ParentPreferenceLinkRequested;
use App\Services\Sms\SmsProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class ParentPreferenceLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('parents/RequestLink');
    }

    /**
     * Look up a contact by exact email/phone match (via the blind-index
     * hash columns) and, only if that specific contact point is verified,
     * email/text a fresh signed link to it. Always responds the same way
     * regardless of whether a match was found, so this can't be used to
     * enumerate which addresses/numbers are on file.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contact' => ['required', 'string', 'max:255'],
        ]);

        $value = trim($data['contact']);
        $hash = hash_hmac('sha256', $value, config('app.key'));

        $contact = ParentContact::query()
            ->where('email_hash', $hash)
            ->orWhere('phone_hash', $hash)
            ->first();

        if ($contact) {
            $this->sendLinkIfVerified($contact, $value);
        }

        return back()->with('success', "If that's on file and verified, we've sent a fresh link.");
    }

    private function sendLinkIfVerified(ParentContact $contact, string $value): void
    {
        $isEmailMatch = $contact->email === $value;
        $verified = $isEmailMatch ? $contact->email_verified_at : $contact->phone_verified_at;

        if (! $verified) {
            return;
        }

        $link = URL::temporarySignedRoute('parents.preferences.show', now()->addDays(7), ['parentContact' => $contact->id]);

        if ($isEmailMatch) {
            Notification::route('mail', $value)->notify(new ParentPreferenceLinkRequested($link));

            return;
        }

        app(SmsProvider::class)->send($value, "Manage your notification preferences: {$link}");
    }
}
