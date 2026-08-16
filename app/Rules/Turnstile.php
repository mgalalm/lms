<?php

namespace App\Rules;

use App\Services\Turnstile\TurnstileClient;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

class Turnstile implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('services.turnstile.secret_key')) {
            Log::warning('Skipped Turnstile verification: no secret key configured.');

            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('Please complete the verification challenge.');

            return;
        }

        try {
            $verified = app(TurnstileClient::class)->verify($value, request()->ip());
        } catch (Throwable $e) {
            Log::error('Turnstile verification request failed.', ['error' => $e->getMessage()]);

            $fail('We couldn\'t verify you\'re human. Please try again.');

            return;
        }

        if (! $verified) {
            $fail('Please complete the verification challenge.');
        }
    }
}
