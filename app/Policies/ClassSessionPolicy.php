<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ClassSession;
use App\Models\User;

class ClassSessionPolicy
{
    /**
     * Determine whether the user can watch this session's recording — the
     * student who attended, the teacher who taught it, or an admin.
     */
    public function viewRecap(User $user, ClassSession $classSession): bool
    {
        return $user->id === $classSession->student_id
            || $user->id === $classSession->teacher_id
            || $user->hasRole(UserRole::Admin->value);
    }
}
