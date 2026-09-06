<?php

namespace App\Http\Controllers\Parents;

use App\Enums\ParentContactChannel;
use App\Enums\ParentContactVerificationResult;
use App\Enums\ParentNotificationChannel;
use App\Enums\ParentNotificationType;
use App\Exceptions\Parents\TooManyVerificationSendsException;
use App\Http\Controllers\Controller;
use App\Models\ParentContact;
use App\Services\Parents\ContactVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ParentPreferenceController extends Controller
{
    public function show(ParentContact $parentContact): Response
    {
        Gate::authorize('manage', $parentContact);

        $parentContact->loadMissing('notificationPreferences', 'student');

        return Inertia::render('parents/Preferences', [
            'parentContact' => [
                'id' => $parentContact->id,
                'name' => $parentContact->name,
                'email' => $parentContact->email,
                'phone' => $parentContact->phone,
                'email_verified' => (bool) $parentContact->email_verified_at,
                'phone_verified' => (bool) $parentContact->phone_verified_at,
                'student_name' => $parentContact->student->name,
            ],
            'notificationTypes' => collect(ParentNotificationType::cases())->map(fn (ParentNotificationType $type) => [
                'value' => $type->value,
                'label' => $type->getLabel(),
                'channel' => $parentContact->preferenceFor($type)->value,
            ]),
            'channelOptions' => collect(ParentNotificationChannel::cases())->map(fn (ParentNotificationChannel $channel) => [
                'value' => $channel->value,
                'label' => $channel->getLabel(),
            ]),
        ]);
    }

    public function update(Request $request, ParentContact $parentContact): RedirectResponse
    {
        Gate::authorize('manage', $parentContact);

        $data = $request->validate([
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'preferences' => ['sometimes', 'array'],
            'preferences.*' => [Rule::enum(ParentNotificationChannel::class)],
        ]);

        if (array_key_exists('email', $data) || array_key_exists('phone', $data)) {
            $parentContact->update([
                'email' => $data['email'] ?? $parentContact->email,
                'phone' => $data['phone'] ?? $parentContact->phone,
            ]);
        }

        foreach ($data['preferences'] ?? [] as $typeValue => $channelValue) {
            $type = ParentNotificationType::tryFrom($typeValue);

            if (! $type) {
                continue;
            }

            $parentContact->notificationPreferences()->updateOrCreate(
                ['notification_type' => $type],
                ['channel' => ParentNotificationChannel::from($channelValue)],
            );
        }

        return back()->with('success', 'Preferences updated.');
    }

    public function verifyCode(Request $request, ParentContact $parentContact, ContactVerificationService $verification): RedirectResponse
    {
        Gate::authorize('manage', $parentContact);

        $data = $request->validate([
            'channel' => ['required', Rule::enum(ParentContactChannel::class)],
            'code' => ['required', 'string'],
        ]);

        $result = $verification->verifyCode($parentContact, ParentContactChannel::from($data['channel']), $data['code']);

        return match ($result) {
            ParentContactVerificationResult::Verified => back()->with('success', 'Verified.'),
            ParentContactVerificationResult::Incorrect => back()->with('error', 'Incorrect code.'),
            ParentContactVerificationResult::Expired => back()->with('error', 'That code has expired — request a new one.'),
            ParentContactVerificationResult::TooManyAttempts => back()->with('error', 'Too many incorrect attempts — request a new code.'),
            ParentContactVerificationResult::NoPendingCode => back()->with('error', 'No pending code — request one first.'),
        };
    }

    public function resendCode(Request $request, ParentContact $parentContact, ContactVerificationService $verification): RedirectResponse
    {
        Gate::authorize('manage', $parentContact);

        $data = $request->validate([
            'channel' => ['required', Rule::enum(ParentContactChannel::class)],
        ]);

        try {
            $verification->sendCode($parentContact, ParentContactChannel::from($data['channel']));
        } catch (TooManyVerificationSendsException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Verification code sent.');
    }
}
