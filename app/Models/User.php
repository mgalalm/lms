<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\QueuedVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, HasRoles, Notifiable;

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
            'gdpr_erased_at' => 'datetime',
            // Encrypted at rest. email stays plaintext deliberately — it's
            // the login lookup key (WHERE email = ?), which an encrypted
            // column (random IV per write) can never match.
            'name' => 'encrypted',
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
     * Get this student's parent/guardian contact. A hasOne today, but kept
     * as its own relation (rather than reused for the HasMany below) so
     * app code that only ever needs "the" contact isn't affected when
     * multiple contacts become possible.
     */
    public function parentContact(): HasOne
    {
        return $this->hasOne(ParentContact::class, 'student_id')->latestOfMany();
    }

    /**
     * This user's timezone, resolved from whichever profile (teacher or
     * student) they have, falling back to UTC for users with neither.
     */
    public function resolvedTimezone(): string
    {
        return $this->teacherProfile?->resolvedTimezone()
            ?? $this->studentProfile?->resolvedTimezone()
            ?? 'UTC';
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
     * Get this user's session package purchases.
     */
    public function sessionPackagePurchases(): HasMany
    {
        return $this->hasMany(SessionPackagePurchase::class);
    }

    /**
     * Get this user's session credit ledger entries.
     */
    public function sessionCreditLedger(): HasMany
    {
        return $this->hasMany(SessionCreditLedgerEntry::class);
    }

    /**
     * This user's current session credit balance, summed from the ledger.
     */
    public function sessionCreditsBalance(): int
    {
        return (int) $this->sessionCreditLedger()->sum('credits');
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

    /**
     * Send the queued variant of the verification email (see
     * QueuedVerifyEmail) so a mail-provider failure can't crash the
     * request that triggered it — e.g. registration.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }

    /**
     * Irreversibly scrub this user's personally identifying information —
     * name, email, login credentials, and parent/guardian contact details
     * — and revoke their roles so they drop off active rosters/leaderboards
     * and can no longer log in.
     *
     * Deliberately anonymizes rather than deletes: session history, chat
     * messages, and payment records (session_package_purchases, the
     * session credit ledger) all stay intact, just no longer identifiable
     * to this person. GDPR's right to erasure doesn't require destroying
     * records a business is legally obligated to retain (payment/tax
     * records) or that belong to the shared history of another
     * participant (a teacher's own session log, a channel's messages) —
     * only the requester's own personal data needs to go.
     *
     * Idempotent — calling this on an already-erased user does nothing.
     */
    public function gdprErase(): void
    {
        if ($this->gdpr_erased_at) {
            return;
        }

        DB::transaction(function () {
            $this->forceFill([
                'name' => 'Deleted User',
                'email' => "deleted-user-{$this->id}@erased.invalid",
                'password' => Hash::make(Str::random(40)),
                'remember_token' => null,
                'gdpr_erased_at' => now(),
            ])->save();

            $this->syncRoles([]);

            // Loaded-instance update, not parentContact()->update(...) —
            // the latter bypasses the `encrypted` cast (see
            // .ai/rules/models.md). Nulling values (never setting a
            // non-null one) means ParentContactObserver's `!== null`
            // guard keeps this from triggering a fresh verification send.
            $this->parentContact?->update([
                'name' => null,
                'email' => null,
                'phone' => null,
            ]);

            $this->teacherProfile?->update([
                'photo_path' => null,
                'bank_name' => null,
                'bank_account_number' => null,
                'team_leader_name' => null,
                'mentor_name' => null,
            ]);
        });
    }
}
