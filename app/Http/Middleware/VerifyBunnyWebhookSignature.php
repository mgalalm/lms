<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBunnyWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * Verifies an HMAC-SHA256 signature of the raw request body against our
     * configured webhook secret. Header name (`X-Bunny-Signature`) follows
     * this project's other webhook integrations (see VerifyZoomWebhookSignature)
     * — reconfirm the exact header Bunny sends against their current docs
     * before relying on this in production.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.bunny_stream.webhook_secret');
        $signature = $request->header('x-bunny-signature');

        $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);

        if (! $signature || ! hash_equals($expected, $signature)) {
            abort(401, 'Invalid Bunny webhook signature.');
        }

        return $next($request);
    }
}
