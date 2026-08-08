<?php

namespace App\Models;

use Database\Factories\StudentProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * Get the user this student profile belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * This student's timezone, falling back to the default when unset.
     */
    public function resolvedTimezone(): string
    {
        return $this->timezone ?: self::DEFAULT_TIMEZONE;
    }
}
