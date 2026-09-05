---
paths:
  - 'app/Filament/**'
---

# Filament

## Don't commit Filament's published public assets — they self-heal via composer
`filament:install` publishes static assets into public/css, public/js, public/fonts. Per Filament's own deployment docs, these are gitignored, not committed — `composer.json`'s `post-autoload-dump` already runs `php artisan filament:upgrade`, which re-publishes them fresh on every `composer install`/`composer update` (including on deploy). Don't remove that composer.json hook, and don't add these public/ paths back into git even if `filament:install` runs again later and recreates them.

Admin panel is at /admin, gated by `User::canAccessPanel()` checking the `admin` Spatie role (see [[general]] rule on roles). Resources live under app/Filament/Resources grouped by feature (Courses, Modules, Lessons under a shared "Curriculum" nav group) — follow that grouping convention for new resources rather than a flat list.

## Action::disabled() is enforced server-side too, not just a UI affordance
Filament's `Action::disabled()` isn't purely cosmetic — `InteractsWithActions` checks `isDisabled()` server-side before running the action's `->action()` closure (mountAction/callAction both no-op silently if disabled). This means:

- In Livewire tests, `->callAction('name', [...])` on a disabled action does NOT execute the closure and sends no notification — assert `->assertActionDisabled('name')` instead of expecting a failure/exception path from calling it.
- You can safely rely on `->disabled(fn () => ...)` as a real guard against an action running with invalid preconditions (e.g. "Generate Quiz" disabled when there's no source text to generate from), not just as a visual hint — though it's still not a substitute for a proper Policy check on routes/controllers reachable outside Filament.

Found while building the lesson quiz generation feature (2026-09-05) — see `EditLesson::getHeaderActions()`'s `generateQuiz` action and `tests/Feature/Filament/LessonQuizAdminTest.php`.
