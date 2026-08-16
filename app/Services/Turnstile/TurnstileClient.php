<?php

namespace App\Services\Turnstile;

use Illuminate\Support\Facades\Http;

class TurnstileClient
{
    private const API_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Verify a Turnstile response token against Cloudflare's siteverify
     * endpoint.
     */
    public function verify(string $token, ?string $remoteIp = null): bool
    {
        $response = Http::asForm()
            ->post(self::API_URL, array_filter([
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $remoteIp,
            ]))
            ->throw();

        return (bool) $response->json('success', false);
    }
}
