---
paths:
  - 'tests/**'
---

# Tests

## Factory-created Online ClassSessions hit the real Zoom API unless zoom_meeting_id is set
`ClassSessionObserver::created()` dispatches `CreateZoomMeetingJob` whenever a session is `type: Online` and `zoom_meeting_id` is empty. QUEUE_CONNECTION=sync in phpunit.xml, so that job runs inline during the test and hits the real Zoom API with placeholder credentials, failing with `RequestException: invalid_client`.

When factory-creating a ClassSession in a test, either pass `'type' => SessionType::Physical` or set an explicit `'zoom_meeting_id' => 'test-...'` to skip the dispatch. This applies to `ClassSession::factory()->create([...])` calls and to any Filament/Livewire form test that creates a session (e.g. via CreateClassSession) with an online type.

## Testing Cashier/Stripe checkout: fake Stripe's cURL client, not Http::fake()
stripe-php uses `Stripe\HttpClient\CurlClient` directly — Laravel's `Http::fake()` does NOT intercept it. To test a route that calls `$user->checkout(...)`, implement `Stripe\HttpClient\ClientInterface` with a fake `request()` method (branch on `$absUrl` for `/v1/customers` and `/v1/checkout/sessions`), then call `\Stripe\ApiRequestor::setHttpClient($fake)` before the request and reset to null via `$this->beforeApplicationDestroyed(...)` after. See tests/Feature/SessionPackagesTest.php.

Also: phpunit.xml sets dummy `STRIPE_KEY`/`STRIPE_SECRET` env vars — without them `Stripe\BaseStripeClient` throws "api_key cannot be the empty string" even when the HTTP client is faked, since key presence is validated before any request is made.
