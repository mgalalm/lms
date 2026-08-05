# Architecture

## High-level shape
Two front-of-house apps, one back-office panel, one API/domain core — all on top of a single Laravel codebase and database.

- **Student Portal** (Inertia + Vue) — rich, custom, interactive UI: chat, gamification, dashboards
- **Teacher Portal** (Inertia + Vue) — same pattern, teacher-specific views (Teaching, LMS, Insights, Requests, Community)
- **Admin/Ops Panel** (Filament) — HR staff managing payroll runs, approving leave, editing policy documents, managing curriculum, viewing cross-teacher insights rollups
- **Laravel core** — auth, business logic, domain models, queues, events shared by all three

## Real-time layer
- **Laravel Reverb** (first-party WebSocket server) + **Laravel Echo** on the Vue side for:
  - Community chat (new messages, typing indicators, unread counts)
  - Live notifications (bell icon, toast confirmations like "Session Feedback Submitted")
  - Live countdown/session-status updates
- Chat messages and notifications go through Laravel queues (monitored with Horizon) so delivery doesn't block requests

## Zoom integration
See [Zoom Integration](./zoom-integration.md) for the full design (meeting creation, host vs. attendee links, attendance-tracking webhooks, recording pipeline, physical-session handling, and operational considerations).

## Frontend structure
- Shared component library between Student and Teacher portals where UI overlaps (Community chat, Next Session bar) — separate route groups but shared `resources/js/Components`
- Tailwind + Tailwind Plus blocks for the many data-heavy screens (Payroll table, Sessions Schedule, Insights tiles, Leave History)
- Lesson viewer component (Recap/Reading/Quiz) can likely be shared between Student and Teacher portals with a `viewer_role` prop, since teachers consume lessons the same way students do when getting certified

## Filament panel scope
Owns anything that's fundamentally a data-management screen for staff:
- Payroll runs, bonus/deduction entry
- Policy document CMS (versioned rich text)
- Leave/HR request approval queues
- Curriculum/module/lesson content management
- Cross-teacher Insights rollups (built-in Filament chart widgets)
- Teacher certification status per course/module (relation manager on the Course resource) — useful for staffing/scheduling decisions

See also: [Tech Stack](./tech-stack.md), [Domain Model](./domain-model.md), [Build Plan](./build-plan.md), [Zoom Integration](./zoom-integration.md)
