<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Twilio\Security\RequestValidator;

class VerifyTwilioWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * Twilio signs the full callback URL plus its POST params (not just
     * the raw body like Zoom's simpler scheme), so this uses the SDK's own
     * RequestValidator rather than a hand-rolled HMAC.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $validator = new RequestValidator((string) config('services.sms.webhook_secret'));

        $signature = (string) $request->header('X-Twilio-Signature');

        if (! $validator->validate($signature, $request->fullUrl(), $request->post())) {
            abort(401, 'Invalid Twilio webhook signature.');
        }

        return $next($request);
    }
}
