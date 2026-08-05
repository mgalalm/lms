# Domain Model

Core entities inferred from the documented feature set (see [Student Dashboard](../product/04-student-dashboard.md) and [Teacher Dashboard](../product/05-teacher-dashboard.md)).

## People
- **User** — polymorphic or role-flagged: student / teacher / admin / parent, with Spatie Laravel-Permission for roles/abilities
- **TeacherProfile** — extended fields seen in the Teacher Profile page: photo, teacher ID, rank, starting date, language(s), faculty/year of graduation, employment type, team affiliations (Tutor Team ID, Team Leader, Mentor), bank/payroll info
- **StudentProfile** — grade level, language, points/coins balance

## Learning content
- **Course** → **Module** → **Lesson** (with Recap video, Reading, Quiz relations)
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
- **Request** — polymorphic model with a `type` enum (Leave, Excused Time, HR Request, Support, Resignation) + JSON payload, since they all share submit → review → approve/reject
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
