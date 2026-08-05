<laravel-boost-guidelines>
=== .ai/conventions rules ===

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

=== .ai/laravel rules ===

## Conventions

- Prefer extracting query/filter logic into Eloquent model scopes instead of embedding it directly in controller actions.

=== .ai/lms-project rules ===

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

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.3. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project keeps committed, area-grouped rules in `.ai/rules` (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
