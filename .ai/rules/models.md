---
paths:
  - 'app/Models/*.php'
---

# Models

## Never name a model/table "Session" — it collides with Laravel's session storage
The product docs call a scheduled tutoring class a "Session," but Laravel's own database session driver (SESSION_DRIVER=database, this project's default) already owns a `sessions` table for login/auth session storage, created in the very first migration. A model/migration named `Session`/`sessions` collides directly with that table.

The tutoring-class concept is modeled as `App\Models\ClassSession` / `class_sessions` table instead. Keep using that name for any future work touching scheduled sessions (Insights, dashboards, Filament resources, etc.) — do not rename back to `Session`.
