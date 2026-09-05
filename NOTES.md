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

First setting: `quiz.max_generations`, default **2**.

- Counts the **first** generation, so default = 1 generate + 1 regenerate
  (user's explicit choice over "1 generate + 2 regenerates").
- **Permanent hard stop** per quiz (user's explicit choice) — not reset by
  editing slide text, no per-quiz reset action. Escape hatch is raising
  the global setting, or editing questions by hand.
- `generation_count` increments **only on success**, inside the
  transaction. My call, not the user's: charging a failed moderation check
  or an API outage against the cap could permanently strand a quiz at 0
  usable questions. Flag if this should be stricter.
- Enforced in `QuizGenerationService::generate()` *before* the API call
  (so a blocked attempt costs nothing), and surfaced in Filament as a
  disabled action + tooltip, a remaining-count in the quiz status line,
  and a remaining-count in the confirm modal.

## What this does

1. Admin pastes a plain-text transcript of a lesson's slides
   (`LessonSlideshow::slides_text`, admin-only, never rendered to students
   or teachers) alongside the existing Google Slides URL field.
2. Admin clicks "Generate Quiz" on the Lesson edit page — dispatches
   `GenerateLessonQuizJob` → `QuizGenerationService`, which calls OpenAI
   (gpt-4o-mini) to produce multiple-choice questions from `slides_text`
   (falls back to `Lesson::reading_content` if no slide text yet).
   Questions save as `LessonQuiz`/`LessonQuizQuestion` rows, status
   `draft`, `is_ai_generated = true`.
3. Admin can fully rewrite/add/remove/reorder questions via a Repeater on
   the same edit page, then "Publish Quiz" to flip status to `published`.
4. Students see a lesson's quiz (radio-button MC, submit, instant score +
   per-question explanation) only once published — enforced via
   `LessonQuizPolicy`, not just hidden in the UI. Grading is stateless (no
   attempt history persisted), same convention as the existing teacher
   certification quiz.

## Key decisions (deviations from the original ask, made explicit to the user)

- **Naming**: used `lesson_quizzes`/`lesson_quiz_questions` (models
  `LessonQuiz`/`LessonQuizQuestion`) instead of `quizzes`/`quiz_questions`
  — those table/model names already exist for the unrelated course-level
  teacher certification quiz (`App\Models\Quiz`/`QuizQuestion`). Confirmed
  naming and this distinction is now recorded in `.ai/rules/models.md` and
  `docs/design/domain-model.md`.
- **Student UI scope**: user asked for a full student quiz-taking UI (not
  just a backend guardrail), added to `CurrentLessonCard.vue`'s existing
  (previously empty-state-only) "Quiz" tab.
- **No teacher access to the new per-lesson quiz** — user clarified their
  "teacher will have the same quiz" comment was about the *existing*,
  unrelated course-certification quiz gate, not a request to extend the
  new lesson quiz to teachers. Verified that existing gate
  (`TeacherCourseCompletion`/`LmsQuizAttemptController`) is untouched by
  this work — full suite still green.
- **Attempts are stateless** (no `LessonQuizAttempt` model) — matches this
  codebase's only precedent (`Teacher\LmsQuizAttemptController`): grade on
  submit, return score via flash data, persist nothing per-attempt.
- **Fixed a latent bug while touching `EditLesson::afterSave()`**: the
  slideshow was previously deleted whenever `slideshow_google_url` was
  blank, which would have silently destroyed `slides_text` for a lesson
  that has slide text but no Google Slides URL set. Now the slideshow is
  only deleted when *both* are blank.

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
- Full suite: `php artisan test --compact` → 403 passed. `vendor/bin/pint
  --dirty`, `npm run build`, `npm run lint` all clean.

## Not done / explicitly out of scope

- No student attempt history/analytics (stateless by design, see above).
- No auto-extraction from the Google Slides API — deliberately deferred
  per the original ask; `slides_text` is designed so that can populate it
  later without a schema change.
