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
     * Bunny doesn't use a separate webhook secret — it signs requests with
     * the library's Read-Only API key: hash_hmac('sha256', $rawBody, $key),
     * sent as the X-BunnyStream-Signature header, alongside an
     * X-BunnyStream-Signature-Version header that must equal 'v1'.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('x-bunnystream-signature-version') !== 'v1') {
            abort(401, 'Invalid Bunny webhook signature.');
        }

        // Raw body, captured before any JSON parsing — parsing and
        // re-serializing would not byte-for-byte match what Bunny signed.
        $rawBody = $request->getContent();

        $readOnlyKey = (string) config('services.bunny.stream_readonly_key');
        $signature = $request->header('x-bunnystream-signature');

        $expected = hash_hmac('sha256', $rawBody, $readOnlyKey);

        if (! $signature || ! hash_equals($expected, $signature)) {
            abort(401, 'Invalid Bunny webhook signature.');
        }

        return $next($request);
    }
}
