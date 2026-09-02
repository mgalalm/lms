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

## Project documentation

`docs/` is the canonical, durable source of truth for the product and system.

- `docs/product/` — product behavior, features, UX, dashboards, notifications, etc.
- `docs/design/` — architecture, domain model, technical design, integrations and build decisions.
- `NOTES.md` — temporary implementation state for the current work: completed work,
  remaining tasks, current issues, relevant files, and next steps.

### Documentation maintenance

As implementation decisions are made or requirements change:

1. Update the relevant canonical document(s) under `docs/`.
2. Update `NOTES.md` with the current implementation/progress state.
3. Check other docs for stale references when a concept, name, domain model,
   behavior, or architecture changes.
4. Do not create duplicate documentation if an existing document is the
   appropriate source of truth.
5. Do not put temporary implementation progress into permanent product/design docs.
6. Before /compact or /clear, ensure important decisions from the conversation
   have been persisted to the appropriate docs and NOTES.md.
