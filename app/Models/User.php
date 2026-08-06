<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the teacher profile associated with the user.
     */
    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    /**
     * Get the student profile associated with the user.
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * Get the sessions this user teaches.
     */
    public function sessionsAsTeacher(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'teacher_id');
    }

    /**
     * Get the sessions this user attends as a student.
     */
    public function sessionsAsStudent(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'student_id');
    }

    /**
     * Get the channels this user participates in.
     */
    public function channels(): BelongsToMany
    {
        return $this->belongsToMany(Channel::class, 'channel_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /**
     * Get the badges this user has earned.
     */
    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class)
            ->using(BadgeUser::class)
            ->withPivot('awarded_at')
            ->withTimestamps();
    }

    /**
     * Get this user's points ledger entries (coins for students, points
     * for teachers — same shared mechanism).
     */
    public function pointsLedger(): HasMany
    {
        return $this->hasMany(PointsLedgerEntry::class);
    }

    /**
     * This user's total points/coins balance, summed from the ledger.
     */
    public function totalPoints(): int
    {
        return (int) $this->pointsLedger()->sum('points');
    }

    /**
     * Scope to users of the given role, ranked by total points (highest
     * first) — powers the student/teacher leaderboards.
     */
    public function scopeLeaderboard(Builder $query, string $role): Builder
    {
        return $query->role($role)
            ->withSum('pointsLedger as total_points', 'points')
            ->orderByDesc('total_points');
    }

    /**
     * Only admins may access the Filament back-office panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole(UserRole::Admin->value);
    }
}
