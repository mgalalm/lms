---
paths:
  - 'app/Models/*.php'
  - app/Models/Message.php
---

# Models

## Never name a model/table "Session" — it collides with Laravel's session storage
The product docs call a scheduled tutoring class a "Session," but Laravel's own database session driver (SESSION_DRIVER=database, this project's default) already owns a `sessions` table for login/auth session storage, created in the very first migration. A model/migration named `Session`/`sessions` collides directly with that table.

The tutoring-class concept is modeled as `App\Models\ClassSession` / `class_sessions` table instead. Keep using that name for any future work touching scheduled sessions (Insights, dashboards, Filament resources, etc.) — do not rename back to `Session`.

## Messages are hidden until moderation clears them — never broadcast/query around Message directly
Every Message goes through OpenAI moderation via MessageObserver (created hook) → ModerateMessageJob (ShouldQueue), fail-safe: no OPENAI_API_KEY configured or an API error both resolve to Message::flagModeration() being the *unsafe* default only for genuine API errors — missing config auto-approves (mirrors CreateZoomMeetingJob's "not configured" pattern) so local/test envs without a key aren't broken. QUEUE_CONNECTION=sync in tests means this all runs inline within the request.

A message only becomes visible to *other* channel participants once `Message::approveModeration()` runs (which is what fires the `MessageSent` broadcast) — the sender always sees their own message regardless of status. Any query surfacing messages to a viewer MUST use `Message::scopeVisibleTo($viewer)` (or `scopeApproved()` for viewer-agnostic contexts like unread counts) — querying `channel->messages()` directly leaks flagged/pending content to other participants. See ChannelController for the pattern (message list, `latestMessage` eager-load constraint, unread count).

Admin review lives at `App\Filament\Resources\FlaggedMessages` (scoped to Pending+Flagged only, not a general message browser) — approving there calls the same `Message::approveModeration()` used by the job, via the shared `FlaggedMessageActions` class.
