---
paths:
  - 'app/Filament/Resources/**/Schemas/*.php'
---

# Schemas

## Dot-notation form fields need mutateFormDataBeforeFill, unlike table columns
A `TextInput::make('user.name')` (or any dot-notation field for a BelongsTo relation) silently hydrates to null on an Edit form — Filament forms resolve state paths as nested array keys, not by traversing the Eloquent relation. This differs from `TextColumn::make('user.name')` in tables/infolists, which DOES resolve correctly via `data_get()` on the model object.

Fix: override `mutateFormDataBeforeFill(array $data): array` on the Edit page and manually inject `$data['user'] = ['name' => $this->record->user->name, ...]`.

Found and fixed in StudentProfileResource and TeacherProfileResource (2026-08-16) — both showed a blank "Student"/"Teacher" name field despite the relation existing. Check any new resource that displays a related model's attribute via dot notation in a form.
