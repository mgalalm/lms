# Codebase Guidelines (Toastmasters)

These notes summarize observed structure and patterns in this repo and serve as a reference for future work.

## Stack and Architecture

- Backend: Laravel + Jetstream + Fortify + Sanctum, with Policies for auth and Resources for API shaping.
- Frontend: Inertia.js + Vue 3 (script setup) + Tailwind CSS + Heroicons.
- Admin: Filament resources are present under `app/Filament`.

## Routing and Controllers

- Web routes live in `routes/web.php` with `can(...)` policy checks per route.
- Controllers return Inertia pages (e.g., `Users/Index`, `Speeches/Show`), usually providing `Resource::collection(...)`.
- Common pattern: use Eloquent `with(...)` to eager load, then let Resources decide fields.
- For destructive actions, routes use `DELETE` and policies guard access.

## Policies

- `app/Policies/*Policy.php` defines view/create/update/delete per model.
- Pattern: admins can do more; non-admins are limited to own records.
- Prefer policy checks at routes for all CRUD endpoints (consistent with existing `can(...)`).

## Models and Relations

- `User` has `speeches`, `evaluations`, and `assignments` relations.
- `Workshop` and `WorkshopAssignment` model relations are used in controllers.
- Use model scopes where available (e.g., `Workshop::available()`).

## Resources (API shaping)

- Use `app/Http/Resources/*` to control payloads sent to Inertia pages.
- Keep resource output consistent; when sending related models in lists, prefer resource collections instead of raw models.
- `whenLoaded(...)` is used to avoid N+1; ensure controllers eager load required relations.

## Frontend Patterns

- Page layout: `AppLayout` is the base layout with nav, `Banner`, and `ConfirmationModalWrapper`.
- Shared UI components live in `resources/js/Components`.
- Pages use `script setup`, `defineProps`, and Inertia `Link`.
- For destructive actions, use `useConfirm()` composable and then `router.delete(...)`.
- Pagination uses `Pagination` component; list pages pass `meta` from resource collections.

## UI Conventions

- Tailwind utility classes are standard; light/dark variants present.
- Tables/lists typically use `divide-*` classes and `bg-white` table surfaces.
- Icons are from `@heroicons/vue/20/solid`.

## Data/UX Conventions

- List views are paginated (`paginate(n)` in controllers).
- Small UI enhancements: masked emails with copy action, inline status pills.
- Use `relativeDate` helper for human-friendly dates.

## Naming and Organization

- Inertia pages live in `resources/js/Pages/<Feature>`.
- Keep `Index.vue`, `Show.vue`, `Create.vue` aligned with controller actions.
- Avoid creating resource classes that collide with model names (see `app/Http/Resources/User.php`).

## Suggested Practices (Derived)

- Always eager load relations needed in the view to avoid N+1.
- Prefer resource collections for nested data instead of returning raw Eloquent models.
- Keep `useConfirm()` call signatures consistent across pages.
- Keep route names aligned with frontend `route('...')` calls.
- Add UI feedback for user actions (copy, delete, etc.) and ensure focus/keyboard behavior for modals.
