# Domain Model

Core entities inferred from the documented feature set (see [Student Dashboard](../product/04-student-dashboard.md) and [Teacher Dashboard](../product/05-teacher-dashboard.md)).

## People
- **User** — polymorphic or role-flagged: student / teacher / admin / parent, with Spatie Laravel-Permission for roles/abilities
- **TeacherProfile** — extended fields seen in the Teacher Profile page: photo, teacher ID, rank, starting date, language(s), faculty/year of graduation, employment type, team affiliations (Tutor Team ID, Team Leader, Mentor), bank/payroll info
- **StudentProfile** — grade level, language, points/coins balance

## Configuration
- **Setting** — key/value store for admin-configurable application settings, edited at /admin → Settings. Keys are constrained to the `SettingKey` enum, which also carries each setting's default, label, helper text and section, so a row only exists once an admin has actually saved a value and adding a setting means adding an enum case (no migration). Values are JSON-cast and read through a single cached array. This is the only settings mechanism — prefer it over `config()` for anything an admin should change at runtime. Current settings: `quiz.max_generations` (default 2), `quiz.passing_score` (default 80), `quiz.max_attempts` (default 3).

## Learning content
- **Course** → **Module** → **Lesson** (with Recap video, Reading, Quiz relations). A course IS a grade band — no separate title, no parallel tracks within a grade. `grade_band` is unique and non-nullable; the four bands (Grade 5 - 6, 7 - 8, 9 - 10, 11 - 12) are fixed reference data seeded once, not admin-creatable. Modules/lessons still have their own titles.
- **LessonSlideshow** — one per Lesson (per locale), admin-authored. Carries the Google Slides embed (`google_slides_url`/`google_slides_id`, shown to teachers) and, separately, `slides_text` (`slides_text_updated_at` tracks edits) — a manually pasted, admin-only plain-text transcript of the slide content, used purely as the source for `LessonQuiz` generation. Manual for now; designed so a later Google Slides API extraction step can populate `slides_text` without a schema change. Never rendered to students or teachers.
- **LessonQuiz** → **LessonQuizQuestion** — a student-facing, AI-generated quiz per Lesson (per locale). Distinct from the course-level teacher certification **Quiz**/**QuizQuestion** (see TeacherCourseCompletion, next) — different tables, different purpose. `QuizGenerationService` builds it from `LessonSlideshow::slides_text` (falling back to `Lesson::reading_content` if no slide text has been pasted yet) via an OpenAI chat-completion call, tagging generated questions `is_ai_generated = true` and hashing the source text (`source_text_hash`) so the admin can see when slide text has drifted since generation. The prompt carries the lesson/module titles as topic context and translates the grade band into an explicit age range with mandatory age-appropriateness constraints; generated questions, options and explanations are then run through the same OpenAI moderation endpoint used for Community chat (`ModerationClient`), and anything flagged fails the whole generation rather than landing in a draft — prompt wording is a request, this is the enforcement. Starts `draft`; an admin publishes it (`LessonQuizStatus::Published`) once reviewed/edited. Students can only ever see/attempt a published quiz — enforced via `LessonQuizPolicy` and `LessonQuiz::scopePublished()`, not just hidden in the UI. Regenerating an existing quiz always replaces its questions (admin confirms first); grading a student's attempt is stateless, same convention as the teacher certification quiz (no attempt history persisted, only the score returned). AI generations per quiz are capped by the `quiz.max_generations` setting (default 2, counting the first generation): `generation_count` increments only on a generation that actually saved questions — so a moderation rejection or an API outage can't strand a quiz at the cap with nothing to show — and is never reset, making the cap permanent per quiz. Admins can always keep editing questions by hand.
- **TeacherLessonQuizAttempt** — one teacher's scored attempt at a lesson's `LessonQuiz`, as a learner. Unlike the student flow (stateless), these are persisted, because they gate certification: a teacher may only mark a lesson complete once they've **passed** that lesson's published quiz (`quiz.passing_score`, default 80), and a lesson with no published quiz **cannot be completed at all**. Attempts are capped per teacher per quiz (`quiz.max_attempts`, default 3, counting the first). A teacher who exhausts them without passing is blocked from completing that lesson — and so from certifying for the course, and from being assigned to teach it — until an admin resets them from the lesson's Quiz Attempts relation manager in Filament. A reset **voids** prior attempts (`voided_at`) rather than deleting them, so the history survives; every count/pass check scopes to `scopeCounting()`. Enforced in `TeacherProfile::canCompleteLesson()` and the two Teacher LMS controllers, not just the UI.
- **TeacherCourseCompletion** — pivot linking TeacherProfile ↔ Course, tracking whether the teacher has completed the course themselves and passed its quiz
  - Gates whether a teacher can be assigned to teach that course/module (see Teacher Dashboard → LMS certification requirement)
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
