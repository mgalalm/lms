# Collapse Course into Grade Band

Full plan lived at `/Users/mgalal/.claude/plans/shimmering-snuggling-metcalfe.md`.

## Status: Done — all three parts complete, verified, local DB rebuilt

## Part 1 — Collapse Course into grade_band

- `app/Console/Commands/AuditCourseTitlesCommand.php` — pre-flight safety
  check (`php artisan courses:audit-titles`). Run before migrating; correctly
  flagged 6 local demo rows with real Faker titles + 2 duplicate grade_bands.
- `database/migrations/2026_09_02_123727_collapse_course_title_into_grade_band.php`
  — drops `title`, makes `grade_band` non-nullable + unique, adds `sort_order`.
- `app/Models/Course.php` — `title` removed from `$fillable`, `sort_order`
  added; `getNameAttribute()` returns `grade_band` for generic "name" callers.
- Filament `Courses` resource — `grade_band` is the primary
  display/searchable/sortable field (display-only in the form —
  `->disabled()->dehydrated(false)`), `sort_order` added, `title` removed
  everywhere (form, table, `$recordTitleAttribute`).
- Full backend + frontend sweep of `->title`/`.title` → `->name`/`grade_band`
  across resources, controllers, notifications, Vue pages/components, and
  `resources/js/types/index.ts` (also tightened `grade_band` to non-nullable
  `string` in `Course`, `LmsCourseCard`, `LmsCourseDetail`).

## Part 2 — Seed the grade bands

- `database/seeders/CourseSeeder.php` — seeds the 4 canonical bands
  (`"Grade 5 - 6"` ... `"Grade 11 - 12"`, spaced format) via `updateOrCreate`
  keyed on `grade_band`, `sort_order` 1–4, `thumbnail_path` left null.
- Registered in `DatabaseSeeder.php` before `DemoDataSeeder`.
- `DemoDataSeeder::seedCurriculumAndCertifications()` — its two ad-hoc
  `Course::factory()->create([...])` calls replaced with
  `Course::where('grade_band', ...)->firstOrFail()` lookups against the
  seeded rows (module/lesson creation underneath unchanged).
- `CourseResource` — `canCreate()`/`canDelete()`/`canDeleteAny()` all return
  `false`; Create/Delete header actions removed from the Filament pages.

## Part 3 — Tests

- `database/factories/CourseFactory.php` — `title` dropped; `grade_band`
  picks from the 4 canonical bands via `fake()->randomElement(...)` (no
  `->unique()`, would exhaust after 4 across a suite run).
- `database/factories/ModuleFactory.php` — **fix beyond the original
  plan, found during verification**: its bare `course_id => Course::factory()`
  default meant every independently-created Module (e.g. 3+ via
  `ClassSession::factory()->count(3)`) rolled its own random `grade_band`
  from only 4 possible values — a real, intermittent collision against the
  new unique constraint (caught via `RoleDashboardTest` failing on one run,
  not always — genuinely flaky before this fix, not a one-off). Fixed by
  reusing an existing Course when one exists, falling back to
  `Course::factory()` only when none does. Verified stable across 4
  consecutive full-suite runs after the fix. `QuizFactory` and
  `TeacherCourseCompletionFactory` also default to `Course::factory()` but
  are never invoked without an explicit `course_id` anywhere in the
  codebase, so left as-is.
- `tests/Feature/Filament/CurriculumAdminTest.php` — list test now uses
  explicit distinct `grade_band`s (`->sequence(...)`); the old create test
  replaced with an assertion that `GET .../courses/create` 403s
  (`canCreate()` is enforced at the route level via Filament's
  `authorizeAccess()`); added a delete-unavailable assertion
  (`assertActionDoesNotExist('delete')` on `EditCourse`).
- `tests/Feature/CourseSeederTest.php` — new: asserts the 4 bands seed in
  the right order, and that running the seeder twice yields 4 courses (not
  8) and preserves an existing `thumbnail_path`.

## Verification (all done)

- `vendor/bin/pint --dirty --format agent` — passed.
- `php artisan test --compact` — 386 passed, run 4 times in a row (post
  ModuleFactory fix) with no flakes.
- `php artisan migrate:fresh --seed` — ran locally (user confirmed, since it
  drops local data): clean migration, courses table now holds exactly the
  4 seeded bands in order with null thumbnails, as expected.
- `npm run build && npm run lint` — both clean.
- No browser/Playwright check — standing instruction for this session to
  skip it.

## Docs sweep (done earlier, same task)

`docs/product/05-teacher-dashboard.md` and `docs/design/domain-model.md`
already updated to describe the Course/grade_band model — see git history/
diff for details, nothing further needed there.

## Not committed

Nothing from this task has been committed yet. Per this session's
established convention, only commit/push when the user explicitly says so.
