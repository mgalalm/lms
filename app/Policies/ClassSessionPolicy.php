<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ClassSession;
use App\Models\User;

class ClassSessionPolicy
{
    /**
     * Determine whether the user can watch this session's recording —
     * the teacher who taught it, or an admin. Not the student: recaps
     * are for teacher review, not student rewatch.
     */
    public function viewRecap(User $user, ClassSession $classSession): bool
    {
        return $user->id === $classSession->teacher_id
            || $user->hasRole(UserRole::Admin->value);
    }
}
