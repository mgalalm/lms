# Domain Model

Core entities inferred from the documented feature set (see [Student Dashboard](../product/04-student-dashboard.md) and [Teacher Dashboard](../product/05-teacher-dashboard.md)).

## People
- **User** — polymorphic or role-flagged: student / teacher / admin / parent, with Spatie Laravel-Permission for roles/abilities
- **TeacherProfile** — extended fields seen in the Teacher Profile page: photo, teacher ID, rank, starting date, language(s), faculty/year of graduation, employment type, team affiliations (Tutor Team ID, Team Leader, Mentor), bank/payroll info
- **StudentProfile** — grade level, language, points/coins balance

## Configuration
- **Setting** — key/value store for admin-configurable application settings, edited at /admin → Settings. Keys are constrained to the `SettingKey` enum, which also carries each setting's default, label, helper text and section, so a row only exists once an admin has actually saved a value and adding a setting means adding an enum case (no migration). Values are JSON-cast and read through a single cached array. This is the only settings mechanism — prefer it over `config()` for anything an admin should change at runtime. Current settings: `quiz.max_generations` (default 2), `quiz.passing_score` (default 80), `quiz.max_attempts` (default 3), `quiz.module_question_count` (default 10).

## Learning content
- **Course** → **Module** → **Lesson** (with Recap video, Reading, Quiz relations). A course IS a grade band — no separate title, no parallel tracks within a grade. `grade_band` is unique and non-nullable; the four bands (Grade 5 - 6, 7 - 8, 9 - 10, 11 - 12) are fixed reference data seeded once, not admin-creatable. Modules/lessons still have their own titles.
- **LessonSlideshow** — one per Lesson (per locale), admin-authored. Carries the Google Slides embed (`google_slides_url`/`google_slides_id`, shown to teachers) and, separately, `slides_text` (`slides_text_updated_at` tracks edits) — a manually pasted, admin-only plain-text transcript of the slide content, used purely as the source for `LessonQuiz` generation. Manual for now; designed so a later Google Slides API extraction step can populate `slides_text` without a schema change. Never rendered to students or teachers.
- **LessonQuiz** → **LessonQuizQuestion** — a student-facing, AI-generated quiz per Lesson (per locale). These questions double as the **bank** the teacher module quiz is assembled from (see TeacherModuleQuizAttempt below) — one set of content serves both audiences. `QuizGenerationService` builds it from `LessonSlideshow::slides_text` (falling back to `Lesson::reading_content` if no slide text has been pasted yet) via an OpenAI chat-completion call, tagging generated questions `is_ai_generated = true` and hashing the source text (`source_text_hash`) so the admin can see when slide text has drifted since generation. The prompt carries the lesson/module titles as topic context and translates the grade band into an explicit age range with mandatory age-appropriateness constraints; generated questions, options and explanations are then run through the same OpenAI moderation endpoint used for Community chat (`ModerationClient`), and anything flagged fails the whole generation rather than landing in a draft — prompt wording is a request, this is the enforcement. Starts `draft`; an admin publishes it (`LessonQuizStatus::Published`) once reviewed/edited. Students can only ever see/attempt a published quiz — enforced via `LessonQuizPolicy` and `LessonQuiz::scopePublished()`, not just hidden in the UI. Regenerating an existing quiz always replaces its questions (admin confirms first); grading a student's attempt is stateless (no attempt history persisted, only the score returned) — unlike teacher module attempts, which are recorded. AI generations per quiz are capped by the `quiz.max_generations` setting (default 2, counting the first generation): `generation_count` increments only on a generation that actually saved questions — so a moderation rejection or an API outage can't strand a quiz at the cap with nothing to show — and is never reset, making the cap permanent per quiz. Admins can always keep editing questions by hand.
- **TeacherModuleCompletion** — **the unit of teacher certification.** A teacher marks a module's lessons complete (self-declared — the assessment is the quiz, not the checkbox), which unlocks the module quiz; passing it records the certification. This is what gates teaching assignment: `ClassSessionForm` requires the teacher to be certified for the **module** of the session's lesson.
- **TeacherModuleQuizAttempt** — one attempt at a module quiz. There is no stored module quiz: `ModuleQuizAssembler` samples the module's per-lesson `LessonQuizQuestion` banks fresh on each attempt, drawing a configured total (`quiz.module_question_count`, default 10) spread as evenly as possible across the lessons that have questions — lessons without a published quiz are skipped rather than blocking the module. Because the draw differs per take, the drawn `question_ids` are stored on the attempt and grading scores against those. Starting again resumes an open attempt rather than redrawing, so a teacher can't reroll for easier questions — but only while the module's question bank is unchanged: each attempt stores a `bank_fingerprint` (hash of the eligible question ids), and if a lesson has since been added or removed, or a quiz published/unpublished/regenerated, the stale draw is replaced **in place** so the content change doesn't cost the teacher an attempt. Rewording a question keeps its id, so it doesn't invalidate an attempt in progress. An open attempt also stays resumable when the cap is already spent — it is itself one of the counted attempts. Attempts are capped (`quiz.max_attempts`, default 3, counting the first) and the pass mark is `quiz.passing_score` (default 80). A teacher who exhausts their attempts stays uncertified until an admin resets them from the module's Quiz Attempts relation manager; a reset **voids** attempts (`voided_at`) rather than deleting them, and every count/pass check scopes to `scopeCounting()`.
- **TeacherCourseCompletion** — a **derived rollup**, not a gate: written automatically once a teacher has passed every module in a course. Kept because Insights' certificate count and the LMS course cards read it. The course-level `Quiz`/`QuizQuestion` pair it used to depend on has been retired — certification is per module now.
  - Completion triggers certificate generation (PDF) and increments the teacher's Certificates stat in Insights

## Sessions
- **Session** — belongs to a Lesson, Student, Teacher; has status, Zoom meeting ID/join URL, tags (e.g. "Instructor Change," "Shift Complain")
- **SessionReview** — post-session feedback: star rating (default 3/"Good"), emoji sentiment; feeds the teacher's Insights scorecard and Flags Breakdown

## Communication
- **Channel** — polymorphic so both DM (student↔teacher) and group channels (e.g. "Birthdays," "Sub team education") reuse the same tables
- **Message** — role-tagged (Student/Tutor), supports rich text, attachments, task/reminder cards
- **Task** — homework sent in-chat to students; also used for the teacher's own internal task list (same model, different `assignable_type`)

## HR / operations
- **Request** — polymorphic model with a `type` enum (Leave, Excused Time, HR Request, General Inquiry, Resignation) + JSON payload, since they all share submit → review → approve/reject. ("General Inquiry" — the underlying enum value is still `support`, only the display label changed to avoid colliding with the unrelated, session-tied support ticket feature.)
- **PolicyDocument** — versioned, category-tagged (Leaves, Meetings, HR Requests, Schedule Change, Sessions, Community, Support, Resignation), with sub-categories for Leaves specifically (Annual, Umrah/Hajj, Paternity, Maternity, Marriage, Sick, Excused Time, Emergency, Death/Condolence, No Show, Public Holidays)
- **LeaveCredit** — live balance tracker per teacher (monthly limit, annual allotment)
- **PayrollEntry** — monthly, per teacher: basic salary, bonus/deduction (with detail breakdown), net amount, status, bank info, completed date

## Gamification
- **Badge** — icon, name, description (e.g. "Quiz Quester," "Python Chatbot Pioneer," "Time Legend")
- **PointsLedger** — shared mechanism for both student coins and teacher points, feeds leaderboards for both roles

## Insights
- **InsightMetric** — per-teacher, per-period rollups: Objections, Flags, Excuses, Study Level, Check-in/Join on-time vs late counts
- **PerformanceScore** — category breakdown (Feedback, Attitude, Preparation, Curriculum, Teaching, Setup) feeding the Total Score percentage

See also: [Architecture](./architecture.md), [Tech Stack](./tech-stack.md), [Build Plan](./build-plan.md)
