# Parent Contact Layer

## Status: Done — implemented, tested, docs updated. Not yet committed (awaiting user review/approval to commit).

Built the full parent contact/verification/preferences/SMS layer described in
`docs/design/domain-model.md` → Parent Contact, and migrated the existing
email-only "Ping Parent" onto it rather than duplicating it.

## Audit findings (Part 0, done first per the task)

Before any code: the existing Ping Parent implementation was
`App\Notifications\ParentPinged` (a queued Notification, mail-only), sent via
`Notification::route('mail', ...)` from `PingParentController`, reading the
address off `StudentProfile::parent_email`. That data — `parent_name`,
`parent_email`, `parent_phone`, `consent_given_at` — already existed on
`student_profiles` (added 2026-08-16, already `encrypted`-cast) and was
already captured at registration (email + consent only; name/phone were
not). This meant Part 5's "migration" was moving real, already-encrypted
data into the new table, not a greenfield build.

## What was built

- **`parent_contacts`** table (student `hasOne` today, no unique constraint
  so `hasMany` works later without restructuring) — `name`/`email`/`phone`
  encrypted, plus `email_hash`/`phone_hash` HMAC-SHA256 blind-index columns
  (new infrastructure, confirmed with the user before building) so the
  "request a link" flow can look up an encrypted column by exact value.
- **`parent_notification_preferences`** — per `ParentNotificationType`
  (urgent_ping/session_reminder/feedback/progress_update/marketing) a
  `ParentNotificationChannel` (sms/email/both/none). `marketing` always
  seeds to `none`.
- **`parent_contact_verifications`** — 6-digit codes, 15 min expiry, 3
  sends/hour (`RateLimiter`), 5 wrong-attempt cap. `ContactVerificationService`
  is the single orchestrator (send/verify), used by registration, the
  Filament admin record, and the parent's own preference page — no
  duplicated logic.
- **`parent_ping_logs`** — audit row per Ping Parent delivery attempt,
  surfaced in a new read-only `ParentPingLogResource` (Safety nav group,
  badge count of recent `no_verified_contact` outcomes).
- **`ParentNotificationRouter`** — resolves a preference into actual
  verified contact points; SMS-only falls back to a verified email
  (one-directional, per spec); nothing verified logs `no_verified_contact`
  rather than dropping silently.
- **SMS layer** — `SmsProvider` interface + `SmsResult` DTO,
  `TwilioSmsProvider` as the first driver (`composer require twilio/sdk`,
  approved by the user), bound in `AppServiceProvider` from
  `config('services.sms.default')`.
- **Magic-link preference page** (`routes/parents.php`) —
  `URL::temporarySignedRoute`, 7-day expiry, no `auth` middleware (parents
  have no account). `ParentContactPolicy::manage` blocks only the `Student`
  role — this, not the missing auth middleware, is what stops a logged-in
  student from using a validly-signed link. "Request a link" looks up by
  `email_hash`/`phone_hash`, only sends to a *verified* contact point, and
  gives the same generic response either way (no enumeration).
- **Ping Parent refactor** — `PingParentController` now does policy
  (`ClassSessionPolicy::pingParent`, replacing the old inline
  `abort_unless`) + rate limiting (`RateLimiter`, checked before dispatch so
  the teacher gets an immediate flash message) + dispatches
  `SendParentPingJob`, which resolves the channel via the router and sends.
  Email leg reuses `ParentPinged` unchanged (just repointed at
  `parentContact` instead of `studentProfile`). SMS copy lives in
  `config/parent_notifications.php` (first config-driven message file in
  this codebase — deliberately narrow, just this one template). Daily cap
  is a new `SettingKey::ParentPingDailyCapPerTeacher` (Settings page, not a
  `.env` value — matches the existing `SettingKey`/`Setting` convention).
- **Optional Twilio status-callback webhook** — `routes/webhooks.php` +
  `VerifyTwilioWebhookSignature` (uses the Twilio SDK's own
  `RequestValidator`, not hand-rolled HMAC — Twilio signs the full URL +
  params, unlike Zoom's simpler raw-body scheme) + CSRF exclusion in
  `bootstrap/app.php`.
- **Legacy data migration** — `php artisan parent-contacts:migrate-legacy
  {--dry-run}`, a console command (not a migration `up()`, so it can
  decrypt/re-encrypt safely, be re-run, and not blast a verification send to
  every legacy row via `withoutEvents()`). The actual
  `drop_parent_guardian_fields_from_student_profiles_table` migration
  refuses to run if it finds any `student_profiles` parent data with no
  matching `parent_contacts` row — the concrete "warn before dropping data"
  guard. Ran locally: 0 legacy rows existed, so nothing to migrate; the drop
  migration ran clean.
- **Registration** — now also captures `parent_name` (required) and
  `parent_phone` (optional) alongside the existing `parent_email`/consent,
  writing to `ParentContact` instead of `StudentProfile`.

## Deviations from the task's literal wording (all deliberate, flagged during planning)

- **PHPUnit, not Pest** — this codebase's entire suite (and CLAUDE.md) is
  PHPUnit; new tests follow that, not the task's "Pest tests" phrasing.
  `PingParentTest.php` and `RegistrationTest.php` were extended in place
  rather than duplicated.
- **`SettingKey`, not a `.env`/config value**, for the per-teacher daily
  cap — matches the documented rule in `.ai/rules/models.md`.

## Key files

- Migrations: `2026_09_05_120000..120003_*`, plus the guarded
  `2026_09_05_190000_drop_parent_guardian_fields_from_student_profiles_table`.
- Models: `App\Models\{ParentContact,ParentNotificationPreference,
  ParentContactVerification,ParentPingLog}`; `ParentContactObserver`.
- Services: `App\Services\Parents\{ContactVerificationService,
  ParentNotificationRouter,ResolvedParentChannel}`,
  `App\Services\Sms\{SmsProvider,SmsResult,TwilioSmsProvider}`.
- `App\Jobs\SendParentPingJob`, `App\Http\Controllers\Teacher\PingParentController`
  (rewritten), `App\Policies\{ClassSessionPolicy::pingParent,ParentContactPolicy}`.
- `App\Http\Controllers\Parents\{ParentPreferenceController,
  ParentPreferenceLinkController}`, `routes/parents.php`,
  `resources/js/pages/parents/{Preferences,RequestLink}.vue`.
- `App\Console\Commands\MigrateLegacyParentContacts`.
- Filament: `app/Filament/Resources/StudentProfiles/RelationManagers/
  ParentContactsRelationManager.php`, `app/Filament/Resources/ParentPingLogs/*`.

## Tests

- `tests/Feature/PingParentTest.php` (rewritten in place) — verified-only
  delivery, SMS→email fallback, `no_verified_contact` logging, per-student
  and per-teacher rate limits, existing email path regression-covered.
- `tests/Feature/Auth/RegistrationTest.php` (updated in place) —
  parent_name required, parent_phone optional, consent/contact data lands
  on `ParentContact`.
- `tests/Feature/Parents/{ContactVerificationTest,PreferencePageTest}.php` —
  code expiry/rate-limit/reset-on-change, signed link expiry/tampering, the
  student-cannot-use-a-valid-link policy test, request-link verified-only +
  rate limited.
- `tests/Unit/ParentContactTest.php` — encryption at rest, blind-index hash
  determinism, marketing-never-defaults-on.
- `tests/Feature/Filament/StudentProfileGdprTest.php` (updated) — erasure
  now targets `ParentContact`, plus a case confirming erasure never
  triggers a verification send.
- `tests/Feature/Filament/ParentPingLogResourceTest.php` — admin
  list/view, immutability, teacher panel-access 403.
- Also fixed 3 unrelated existing tests whose registration payloads needed
  `parent_name` added (`TurnstileVerificationTest`, `ReferralRewardsTest`
  ×2) and one Filament nav-audit test that needed
  `ParentPingLogResource::getGloballySearchableAttributes()`.
- Full suite: `php artisan test --compact` → 478 passed (2137 assertions).
  `vendor/bin/pint --dirty`, `npm run build`, `npm run lint` all clean.

## Not done / explicitly deferred

- No automated test drives the Vue preference page end-to-end (Preferences.vue/
  RequestLink.vue) — covered at the HTTP/controller layer only. Backend
  behavior is fully tested; the frontend hasn't been clicked through in a
  real browser.
- `ParentPingLog.message` stores the rendered SMS/email body verbatim —
  fine at current volume, but worth revisiting if this table grows large
  and PII-retention policy needs a shorter window.
