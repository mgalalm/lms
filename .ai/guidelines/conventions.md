## Application Conventions (Inertia + Vue + Filament)

These are reusable architectural patterns carried over from prior project
experience. Some items below are flagged with [CONFIRM] where they may
conflict with this project's actual installed stack — resolve before treating
as settled convention.

### Routing and Controllers

- Web routes use `can(...)` policy checks per route rather than in-controller
  authorization checks.
- Controllers return Inertia pages, typically providing `Resource::collection(...)`
  rather than raw Eloquent models/collections.
- Eager load relations with `with(...)` in the controller/query, then let API
  Resources decide which fields are exposed.
- Destructive actions use `DELETE` routes guarded by policies.

### Policies

- One policy per model (`app/Policies/*Policy.php`) covering view/create/update/delete.
- [CONFIRM] Prior pattern was a simple admin-vs-own-records split. This project
  has three roles (student/teacher/admin) — policies should reflect the actual
  role model in `docs/design/domain-model.md`, not assume a binary admin/non-admin
  split.
- Prefer policy checks at the route level for all CRUD endpoints, consistently.

### Resources (API shaping)

- Use `app/Http/Resources/*` to control the payload shape sent to Inertia pages.
- Prefer resource collections over raw Eloquent models/collections for any
  nested/related data sent to the frontend.
- Use `whenLoaded(...)` in Resources and ensure controllers eager load the
  corresponding relations, to avoid N+1s surfacing at the Resource layer.
- Avoid naming a Resource class the same as its underlying Model (e.g. an
  `App\Http\Resources\User` resource for an `App\Models\User` model) — pick a
  distinct name to avoid import collisions.

### Frontend Structure

- [CONFIRM] Icon library: prior convention was `@heroicons/vue/20/solid`. This
  project's starter kit (`laravel/vue-starter-kit`) ships shadcn-vue, which
  typically pairs with `lucide-vue-next` — confirm which icon set to standardize
  on before scaffolding many components.
- Inertia pages live in `resources/js/pages/<Feature>` (lowercase, per this
  project's starter kit — not `Pages/`).
- Shared UI components live in `resources/js/components` (lowercase).
- Pages use `<script setup>`, `defineProps`, and Inertia's `<Link>` component.
- Keep `Index.vue`, `Show.vue`, `Create.vue`, etc. aligned 1:1 with the
  corresponding controller actions.
- For destructive actions, use a confirmation composable (e.g. `useConfirm()`)
  before calling `router.delete(...)` — don't fire destructive requests
  directly off a click handler.
- Paginated list views: pass `meta` through from the Resource collection to a
  shared `Pagination` component rather than reimplementing pagination UI per page.

### UI Conventions

- Tailwind utility classes are the standard; support light/dark variants where
  the design calls for it.
- Tables/lists typically use `divide-*` classes on a `bg-white` (or themed)
  surface rather than manual borders.
- For sensitive-but-visible data (e.g. emails, credentials, account numbers —
  relevant here given the Teacher Credentials/Profile pages documented in
  `docs/05-teacher-dashboard.md`), mask the value by default and provide a
  copy-to-clipboard action rather than displaying it in full.
- Use inline status pills for state (e.g. Approved/Pending, On Time/Late) —
  matches patterns already documented in the Payroll and Requests History
  sections of `docs/05-teacher-dashboard.md`.
- Use a `relativeDate` helper for human-friendly timestamps (e.g. "2H ago")
  rather than raw datetime strings, where the UI calls for it — matches the
  channel activity timestamps documented in the Community sections.

### General Suggested Practices

- Always eager load relations needed by the view to avoid N+1 queries.
- Prefer resource collections for nested data instead of returning raw
  Eloquent models.
- Keep confirmation-composable call signatures consistent across pages.
- Keep route names aligned with frontend `route('...')` calls.
- Add UI feedback for user actions (copy, delete, submit, etc.) and ensure
  reasonable focus/keyboard behavior for modals.
