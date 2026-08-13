---
paths:
  - 'database/migrations/**'
---

# Migrations

## MySQL 64-char identifier limit hits long FK constraint names
A `foreignId('session_package_purchase_id')->constrained()` on a long table name (e.g. `session_credit_ledger_entries`) can produce a Laravel-generated constraint name over MySQL's 64-char identifier limit ("Identifier name ... is too long"), which SQLite silently tolerates so this only surfaces against MySQL. Pass an explicit short name: `->constrained(indexName: 'short_name_foreign')`. If a migration fails partway through `Schema::create` after the table was already physically created (e.g. this FK error), the table exists but isn't marked as migrated — `php artisan migrate` will then fail with "table already exists" on retry; drop the orphaned table manually (`Schema::dropIfExists(...)` via tinker) before re-running.
