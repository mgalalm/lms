---
paths:
  - 'routes/**'
  - routes/channels.php
---

# Routes

## Zoom webhook route lives in routes/webhooks.php, excluded from CSRF
Inbound Zoom webhooks (meeting.started/ended, participant_joined, recording.completed, endpoint.url_validation) are handled by App\Http\Controllers\ZoomWebhookController at POST /webhooks/zoom, defined in routes/webhooks.php (required from routes/web.php).

Because it's an external, unauthenticated endpoint, `webhooks/zoom` is excluded from CSRF verification via `validateCsrfTokens(except: [...])` in bootstrap/app.php — don't remove that exclusion or move the route without keeping it.

Signature verification (Zoom's v0=hmac-sha256 scheme) happens in App\Http\Middleware\VerifyZoomWebhookSignature, applied only to this route — not globally. The endpoint.url_validation handshake event is exempt from signature checking since it arrives before Zoom has confirmed our secret.

Any new inbound webhook from a third party (not just Zoom) should follow this same pattern: routes/webhooks.php + dedicated signature-verification middleware + CSRF exclusion in bootstrap/app.php, rather than living in routes/web.php directly.

## Broadcast::channel() patterns are per-connection, not global
`Broadcast::channel('channel.{channel}', ...)` in routes/channels.php registers the pattern onto whichever broadcaster connection is `config('broadcasting.default')` *at the moment the file loads* (app boot) — not onto all connections. Switching the default connection afterward (e.g. `config(['broadcasting.default' => 'reverb'])` mid-test) resolves a brand-new, separate broadcaster instance with an empty channel registry, so `/broadcasting/auth` falls through to the framework's default-deny and returns 403 regardless of the actual policy logic.

Also: tests use `BROADCAST_CONNECTION=log` (see phpunit.xml) so `broadcast()` calls in feature tests don't try to hit a real Reverb server — but the `log` and `null` broadcasters' `auth()` methods are no-ops by design (they never call `verifyUserCanAccessChannel`), so `/broadcasting/auth` always returns 200 under them regardless of whether the channel callback would actually authorize. A test asserting broadcasting auth must switch `config(['broadcasting.default' => 'reverb'])` *and* re-register the channel pattern against that connection in the same test (see `CommunityChatTest::useRealBroadcastAuth()`) — Reverb's `auth()` runs the real policy check purely locally, no network call needed.
