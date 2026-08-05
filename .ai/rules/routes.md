---
paths:
  - 'routes/**'
---

# Routes

## Zoom webhook route lives in routes/webhooks.php, excluded from CSRF
Inbound Zoom webhooks (meeting.started/ended, participant_joined, recording.completed, endpoint.url_validation) are handled by App\Http\Controllers\ZoomWebhookController at POST /webhooks/zoom, defined in routes/webhooks.php (required from routes/web.php).

Because it's an external, unauthenticated endpoint, `webhooks/zoom` is excluded from CSRF verification via `validateCsrfTokens(except: [...])` in bootstrap/app.php — don't remove that exclusion or move the route without keeping it.

Signature verification (Zoom's v0=hmac-sha256 scheme) happens in App\Http\Middleware\VerifyZoomWebhookSignature, applied only to this route — not globally. The endpoint.url_validation handshake event is exempt from signature checking since it arrives before Zoom has confirmed our secret.

Any new inbound webhook from a third party (not just Zoom) should follow this same pattern: routes/webhooks.php + dedicated signature-verification middleware + CSRF exclusion in bootstrap/app.php, rather than living in routes/web.php directly.
