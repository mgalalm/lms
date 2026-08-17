---
paths:
  - 'app/Filament/Resources/**'
---

# Filament Resources

## Encrypting a shared column (e.g. User.name) breaks every dot-path referencing it
Adding an `encrypted` cast to a column used across relations (e.g. `User::name`) breaks `->searchable()`/`->sortable()` and `getGloballySearchableAttributes()` on every Filament resource that references it via ANY dot-path — not just `user.name`, but `teacher.name`, `teacherProfile.user.name`, `classSession.student.name`, etc. Grep the whole `app/Filament` tree for the bare column name in dot-paths before encrypting, not just the obvious direct references.

Fix pattern used for `User::name` (2026-08-16): swap `.name` → `.email` in `getGloballySearchableAttributes()` (email stays plaintext/queryable), and drop `->searchable()`/`->sortable()` from the corresponding table columns (sorting ciphertext is meaningless, not just broken). For a relation-manager attach/associate picker, use `->recordSelectSearchColumns(['email'])` instead of changing `recordTitleAttribute()`, so the picker still *displays* the friendly name while *searching* the plaintext column.

Also check non-Filament app code for raw `where('name', 'like', ...)` queries — found and fixed one in `App\Http\Controllers\Teacher\InsightsController` (a live teacher-facing search), rewritten to filter the already-scoped/bounded result set in PHP post-decryption rather than dropping the feature. See [[models]] for the companion `->relation()->update()` cast-bypass trap.
