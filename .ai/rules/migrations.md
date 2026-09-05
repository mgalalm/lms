---
paths:
  - 'database/migrations/**'
---

# Migrations

## MySQL 64-char identifier limit hits long FK constraint names
A `foreignId('session_package_purchase_id')->constrained()` on a long table name (e.g. `session_credit_ledger_entries`) can produce a Laravel-generated constraint name over MySQL's 64-char identifier limit ("Identifier name ... is too long"), which SQLite silently tolerates so this only surfaces against MySQL. Pass an explicit short name: `->constrained(indexName: 'short_name_foreign')`. If a migration fails partway through `Schema::create` after the table was already physically created (e.g. this FK error), the table exists but isn't marked as migrated — `php artisan migrate` will then fail with "table already exists" on retry; drop the orphaned table manually (`Schema::dropIfExists(...)` via tinker) before re-running.

## Two make:migration calls in the same second sort by filename, not creation order
`php artisan make:migration` timestamps only have second precision. Two migrations created in the same second (e.g. via two back-to-back `make:migration` calls, or parallel tool calls) get identical timestamp prefixes and then sort alphabetically by name — which does NOT necessarily match the dependency order you need (e.g. a `create_x_table` migration whose FK depends on a `create_y_table` migration created in the same second can alphabetize before it and fail with "Failed to open the referenced table").

Fix: bump the later migration's filename timestamp by at least one second so `migrate:status` shows the FK-dependent one after its dependency, and verify with `php artisan migrate:status` before running.

If a `Schema::create` migration fails partway (FK error, etc.) after the table was already physically created, the table exists but isn't marked as migrated — retrying `migrate` then fails with "table already exists". Drop the orphaned table first (e.g. via `php artisan tinker --execute 'Schema::dropIfExists("table")'`), then re-run. See also the existing note in this file about the MySQL 64-char identifier limit — same underlying failure-recovery pattern.
