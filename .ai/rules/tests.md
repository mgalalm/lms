---
paths:
  - 'tests/**'
---

# Tests

## Factory-created Online ClassSessions hit the real Zoom API unless zoom_meeting_id is set
`ClassSessionObserver::created()` dispatches `CreateZoomMeetingJob` whenever a session is `type: Online` and `zoom_meeting_id` is empty. QUEUE_CONNECTION=sync in phpunit.xml, so that job runs inline during the test and hits the real Zoom API with placeholder credentials, failing with `RequestException: invalid_client`.

When factory-creating a ClassSession in a test, either pass `'type' => SessionType::Physical` or set an explicit `'zoom_meeting_id' => 'test-...'` to skip the dispatch. This applies to `ClassSession::factory()->create([...])` calls and to any Filament/Livewire form test that creates a session (e.g. via CreateClassSession) with an online type.
