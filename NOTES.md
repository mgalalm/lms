# Lesson Slide Text + AI Quiz Generation (+ first App Settings)

## Status: Done — implemented, tested, docs updated, pushed to main.

## App Settings (new, first of its kind)

`settings` table + `App\Models\Setting` + `App\Enums\SettingKey` +
`App\Filament\Pages\Settings` (new "Settings" nav group, last in the
sidebar — configuration, not day-to-day ops).

- Adding a setting = add a `SettingKey` case (default/label/helper/section)
  + a component in `Settings::componentFor()` + a cast in `castValue()`.
  No migration, no new page.
- `Setting::get(SettingKey::Foo)` falls back to the enum default when no
  row exists; `Setting::set()` busts the cache.
- **Deliberately built in-house, no package.** CLAUDE.md forbids adding
  dependencies without approval, so `spatie/laravel-settings` and the
  Filament settings plugin were both avoided.
- Trap (recorded in `.ai/rules/models.md`): the cached read uses
  `static::all()` + `mapWithKeys`, NOT `pluck('value','key')` — a
  query-builder pluck bypasses the JSON cast.

Settings so far: `quiz.max_generations` (2), `quiz.passing_score` (80),
`quiz.max_attempts` (3), `quiz.module_question_count` (10).

## Certification moved to MODULE level (2026-09-05, supersedes the below)

The earlier lesson-completion gate was the wrong rung and has been
**reverted**. Marking a lesson complete is self-declared again. The
assessment is now a **module quiz**, which completing a module's lessons
unlocks.

- **Module quiz replaces the course quiz.** `Quiz`/`QuizQuestion` (the
  old course-level certification quiz) and `LmsQuizAttemptController` are
  **retired/deleted**. The tables still exist but nothing reads them —
  drop them in a later cleanup migration if you want.
- `TeacherModuleCompletion` is now the unit of certification.
  `TeacherCourseCompletion` survives as a **derived rollup** (written when
  every module in the course is passed) purely so Insights' certificate
  count and the LMS course cards keep working.
- **Assignment gate is now per module** — `ClassSessionForm` checks
  `isCertifiedForModule($lesson->module)`, not the course.
- **No stored module quiz.** `ModuleQuizAssembler` samples the module's
  per-lesson `LessonQuizQuestion` banks fresh on each attempt: a fixed
  total (`quiz.module_question_count`, default 10) spread evenly across
  lessons that have questions, one-per-lesson-per-pass so a small bank
  drops out and the rest redistribute. Lessons with no published quiz are
  skipped, not blocking.
- Drawn `question_ids` are stored on the attempt so grading scores what
  was actually shown.
- **"Start Quiz" always redraws** — it doubles as the refresh control
  (user's explicit call: "I need just to refresh the questions by
  clicking on the start quiz button"). The button stays visible while
  questions are on screen, labelled "Start Quiz (new questions)".
  - Redraw is **in place** on the open attempt, so refreshing never
    consumes extra attempts — an attempt is spent by existing, not per
    draw. Verified by test.
  - **Deliberate trade**: a teacher can restart until they like the draw.
    The user explicitly relaxed this ("doesn't need to be that restrict
    for now so they can look around it") after I proposed a timed-sitting
    model to close it. If exam rigour is ever wanted, the plan was:
    `expires_at` per attempt + auto-finalise on expiry, which makes
    previewing cost an attempt.
  - `bank_fingerprint` is still recorded but is now **informational** —
    it was the key for resume-vs-redraw under the old rule. Left in place
    rather than dropped, since it's what a stricter rule would key off.
- **Bug found and fixed while explaining the flow**: the resume lookup
  originally sat *after* the attempt-cap guard. Since an open attempt
  already counts toward the cap, a teacher who started their *final*
  attempt and navigated away got a 403 trying to return, and the page
  showed "used all attempts" over their open quiz. Resume now runs before
  the cap check, and the payload carries `has_open_attempt` so the UI
  offers the open quiz. Regression-tested both ways.
- Attempts capped (`quiz.max_attempts`, 3, counting the first); admin
  reset voids rather than deletes, now on the **Module** resource's Quiz
  Attempts relation manager (moved off Lesson).
- Lesson-level teacher attempt code from the previous iteration
  (`TeacherLessonQuizAttempt`, its table/factory/controller/relation
  manager and test) was deleted outright — zero rows existed.

## Key files

- Migrations: `2026_09_05_074637_add_slides_text_to_lesson_slideshows_table.php`,
  `2026_09_05_074638_create_lesson_quizzes_table.php`,
  `2026_09_05_074639_create_lesson_quiz_questions_table.php` (note: the
  quizzes/quiz_questions pair originally shared an identical timestamp and
  had to be re-ordered — see the new rule in `.ai/rules/migrations.md`).
- `App\Services\QuizGenerationService` + `App\Services\OpenAI\QuizGenerationClient`
  — generation + validation; throws with a clear message on empty source
  text or malformed OpenAI output (never saves malformed questions).
  Age-appropriateness is enforced in two layers: the prompt translates the
  grade band into a concrete age range (grade + 5) with mandatory content
  constraints and carries the lesson/module titles as topic context, and
  everything generated is then run through `ModerationClient` (the same
  endpoint Community chat uses) — flagged content, or a moderation
  outage, fails the generation closed rather than saving a draft.
  `source_text_hash` fingerprints slide text + titles + grade band, so
  retitling or re-banding a lesson trips the staleness warning too.
- `App\Jobs\GenerateLessonQuizJob` — dispatched via `Bus::dispatchNow` from
  the Filament action (same pattern as `SyncNotionContentJob`), so the
  admin gets an immediate success/failure notification.
- `App\Policies\LessonQuizPolicy`, `LessonQuiz::scopePublished()` — the
  actual student-visibility enforcement (`DashboardController` and
  `Student\LessonQuizAttemptController` both check it).
- `app/Filament/Resources/Lessons/{Schemas/LessonForm.php,Pages/EditLesson.php}`
  — slides_text field, Quiz section (Repeater, drag-to-reorder, AI badge
  via item label + an `is_ai_generated` toggle), Generate/Publish actions.
- `resources/js/components/dashboard/{CurrentLessonCard.vue,LessonQuizPanel.vue}`
  — student-facing quiz tab.
- `App\Services\ModuleQuizAssembler` — the balanced random draw.
- `App\Http\Controllers\Teacher\LmsModuleQuizAttemptController` —
  start (draws + records) and submit (grades + certifies + rolls up).
- `app/Filament/Resources/Modules/RelationManagers/QuizAttemptsRelationManager.php`
  — admin reset.
- `resources/js/pages/teacher/Lms/Show.vue` — per-module quiz section.

## Tests

- `tests/Feature/LessonQuizGenerationTest.php` — service-level: generates
  from slides_text, falls back to reading_content, throws on empty
  source, fails loudly on malformed AI output, regeneration replaces
  questions, prompt carries titles + age range (and omits the age for an
  unrecognised band), moderation-flagged content and moderation outages
  both discard the generation, moderation sees every student-visible
  field. Note its `fakeOpenAiResponse()` helper fakes both OpenAI
  endpoints — an `api.openai.com/*` catch-all would silently answer the
  moderation call with a chat-shaped body.
- `tests/Feature/Filament/LessonQuizAdminTest.php` — admin generate
  (mocked OpenAI via `Http::fake`), generate action disabled + blocked
  server-side when no slide text, manual edits survive a save, publish
  flips status, regenerate-with-existing-questions shows the overwrite
  warning.
- `tests/Feature/Filament/SettingsTest.php` — default fallback with no
  row, typed round-trip (guards the pluck/cast trap), cache busting on
  write, admin can view/save, validation rejects < 1, non-admin 403s.
- `tests/Feature/LessonQuizVisibilityTest.php` — draft quiz excluded from
  the student dashboard payload and from submission (403); published quiz
  is visible (answers never sent to the client) and scores correctly;
  non-students can't submit at all.
- `tests/Feature/ModuleQuizAssemblerTest.php` — draws the configured
  count, spreads evenly across lessons, a small bank redistributes rather
  than shortening the draw, falls short gracefully when the whole bank is
  too small, skips draft/quiz-less lessons, successive draws differ.
- `tests/Feature/TeacherModuleQuizAttemptTest.php` — start records the
  draw, restart resumes rather than rerolls, pass certifies, fail
  doesn't, grading scores only drawn questions, cap + voided attempts +
  already-certified + setting-driven cap, page only exposes questions
  while an attempt is open.
- `tests/Feature/Filament/ModuleQuizAttemptsAdminTest.php` — admin reset.
- Full suite: `php artisan test --compact` → 442 passed. `vendor/bin/pint
  --dirty`, `npm run build`, `npm run lint` all clean.

## Not done / explicitly out of scope

- The retired `quizzes`/`quiz_questions` tables are still in the schema,
  unreferenced. No cleanup migration written — deliberate, so nothing is
  dropped without a decision.
- No student attempt history/analytics (stateless by design, see above).
- No auto-extraction from the Google Slides API — deliberately deferred
  per the original ask; `slides_text` is designed so that can populate it
  later without a schema change.
