---
paths:
  - 'app/Filament/**'
---

# Filament

## Don't commit Filament's published public assets — they self-heal via composer
`filament:install` publishes static assets into public/css, public/js, public/fonts. Per Filament's own deployment docs, these are gitignored, not committed — `composer.json`'s `post-autoload-dump` already runs `php artisan filament:upgrade`, which re-publishes them fresh on every `composer install`/`composer update` (including on deploy). Don't remove that composer.json hook, and don't add these public/ paths back into git even if `filament:install` runs again later and recreates them.

Admin panel is at /admin, gated by `User::canAccessPanel()` checking the `admin` Spatie role (see [[general]] rule on roles). Resources live under app/Filament/Resources grouped by feature (Courses, Modules, Lessons under a shared "Curriculum" nav group) — follow that grouping convention for new resources rather than a flat list.
