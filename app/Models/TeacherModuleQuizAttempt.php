<?php

namespace App\Models;

use Database\Factories\TeacherModuleQuizAttemptFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * One teacher's attempt at a module's certification quiz. Each attempt
 * samples the module's per-lesson question banks fresh (see
 * ModuleQuizAssembler), so the drawn question_ids are stored on the
 * attempt — grading scores against what was actually shown.
 */
class TeacherModuleQuizAttempt extends Model
{
    /** @use HasFactory<TeacherModuleQuizAttemptFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_profile_id',
        'module_id',
        'question_ids',
        'bank_fingerprint',
        'score',
        'passed',
        'submitted_at',
        'voided_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'question_ids' => 'array',
            'score' => 'integer',
            'passed' => 'boolean',
            'submitted_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Attempts that still count — an admin reset voids attempts rather
     * than deleting them, so every count/pass check must scope to these.
     *
     * @param  Builder<TeacherModuleQuizAttempt>  $query
     * @return Builder<TeacherModuleQuizAttempt>
     */
    public function scopeCounting(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }

    /**
     * The questions this attempt drew, in the stored order.
     *
     * @return Collection<int, LessonQuizQuestion>
     */
    public function questions(): Collection
    {
        $questions = LessonQuizQuestion::whereIn('id', $this->question_ids)->get()->keyBy('id');

        return collect($this->question_ids)
            ->map(fn (int $id) => $questions->get($id))
            ->filter()
            ->values();
    }
}
