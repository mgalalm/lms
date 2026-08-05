# Build Plan

Suggested phased build order for a modern rebuild of the LMS platform.

1. **Auth + roles + core profiles** — Laravel Fortify/Sanctum + Spatie Laravel-Permission; TeacherProfile/StudentProfile models
2. **Sessions + Zoom integration** — the critical path feature; Session model, Zoom Server-to-Server OAuth, meeting creation job, webhook listener for status updates
3. **Student/Teacher dashboards** (Inertia) — Next Session bar, Sessions Schedule
4. **Community chat** — Reverb + Echo, Channel/Message models, rich text composer (Tiptap), task/reminder cards
5. **Filament admin** — policies, payroll, requests (can build in parallel to step 4, low overlap with the Inertia work)
6. **Insights/analytics** — once session/attendance data exists to aggregate; Performance charts, Flags Breakdown, Total Score
7. **Gamification** (badges, points, leaderboard) — layer on last, it's additive

## Open questions / things to verify from more screenshots
- Exact relationship between course completion (LMS tab) and session assignment eligibility — is it a hard gate or just tracked?
- Whether "Meetings" and "Schedule Change" (seen as Policy categories) have matching Request card types elsewhere
- What "Study Level" measures in Insights
- Whether Teaching → Projects (sub-nav tab) mirrors LMS content or is a separate project-based assignment system

See also: [Architecture](./architecture.md), [Tech Stack](./tech-stack.md), [Domain Model](./domain-model.md)
