## LMS Project Context

This is a rebuild of an existing LMS platform (Zoom-based live tutoring, separate
Teacher and Student dashboards, plus HR/payroll tooling for teachers).

- Full product spec, screenshots-derived feature docs, and architecture decisions
  live in `docs/` — read `docs/00-index.md` first before working on any feature,
  then the specific linked file (e.g. `docs/05-teacher-dashboard.md`,
  `docs/design/domain-model.md`, `docs/design/zoom-integration.md`).
- Key business rule: a teacher must complete a course themselves (including its
  quiz) before being assigned to teach it — this is a certification gate, not
  just a progress tracker. See `docs/05-teacher-dashboard.md` → LMS (Nav Tab).
- Every "Join"/"Join Now" button opens Zoom directly — see
  `docs/design/zoom-integration.md` for the exact webhook/meeting-creation flow.
- Don't deviate from the Inertia-portals + Filament-admin split documented in
  `docs/design/architecture.md` without asking first.
