<?php

namespace App\Models;

use Database\Factories\StudentProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentProfile extends Model
{
    /** @use HasFactory<StudentProfileFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'code',
        'grade_level',
        'language',
        'timezone',
    ];

    /**
     * Default timezone used wherever this student hasn't set one.
     */
    public const DEFAULT_TIMEZONE = 'Europe/Dublin';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Get the user this student profile belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convenience delegate to the student's parent/guardian contact —
     * lives on ParentContact (keyed off the User), not on this profile.
     */
    public function parentContact(): HasOne
    {
        return $this->hasOne(ParentContact::class, 'student_id', 'user_id')->latestOfMany();
    }

    /**
     * Returns 0-or-1 rows today (parent_contacts.student_id has no unique
     * constraint, by design) — exists so Filament's
     * ParentContactsRelationManager can plug into a HasMany, even while
     * cardinality stays 1.
     */
    public function parentContacts(): HasMany
    {
        return $this->hasMany(ParentContact::class, 'student_id', 'user_id');
    }

    /**
     * This student's timezone, falling back to the default when unset.
     */
    public function resolvedTimezone(): string
    {
        return $this->timezone ?: self::DEFAULT_TIMEZONE;
    }
}
