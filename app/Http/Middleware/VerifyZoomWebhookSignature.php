<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyZoomWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // The url_validation handshake is sent before Zoom has confirmed our
        // secret, so it carries no signature to verify.
        if ($request->input('event') === 'endpoint.url_validation') {
            return $next($request);
        }

        $secret = config('services.zoom.webhook_secret');
        $signature = $request->header('x-zm-signature');
        $timestamp = $request->header('x-zm-request-timestamp');

        $expected = 'v0='.hash_hmac('sha256', "v0:{$timestamp}:{$request->getContent()}", (string) $secret);

        if (! $signature || ! hash_equals($expected, $signature)) {
            abort(401, 'Invalid Zoom webhook signature.');
        }

        return $next($request);
    }
}
