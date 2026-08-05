# Tech Stack

## Chosen stack
- **Backend**: Laravel
- **Frontend**: Vue 3 + Inertia.js
- **Admin panel**: Filament
- **Styling**: Tailwind CSS + **Tailwind Plus** (UI blocks)

## Rationale
- Laravel stays the single source of truth (auth, business logic, domain models, queues, events) — no duplicated logic between the Inertia app and Filament panel, since both read/write the same Eloquent models
- Inertia + Vue powers the bespoke, interactive experiences (chat, gamification, dashboards) that don't fit an admin-CRUD pattern
- Filament powers the back-office/ops panel — anything that's fundamentally data management (payroll, policies, leave approvals, curriculum content) is faster to build here than hand-rolled Vue admin screens
- Tailwind Plus blocks (stats/KPI cards, tables, calendars, feeds, form layouts, sidebars) get dropped straight into Inertia pages since they're just Tailwind markup — cuts down on UI decision fatigue for the many data-table/dashboard screens this product needs

## Suggested libraries
- **Auth/roles**: Laravel Fortify or Sanctum + Spatie Laravel-Permission
- **Real-time**: Laravel Reverb (first-party WebSocket server) + Laravel Echo on the Vue side
- **Queues/monitoring**: Laravel Horizon
- **Client state**: Pinia
- **Rich text editor** (chat composer): Tiptap (Vue bindings)
- **Charts** (Insights bar charts, Total Score): ApexCharts or Chart.js via vue-chartjs — Filament also has built-in chart widgets for the admin-side rollups
- **PDF generation** (certificates): barryvdh/laravel-dompdf

See also: [Architecture](./architecture.md), [Domain Model](./domain-model.md), [Build Plan](./build-plan.md)
