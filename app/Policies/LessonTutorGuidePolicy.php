<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LessonTutorGuide;
use App\Models\User;

/**
 * Teacher/admin-only lesson prep material — unlike LessonVideo, this has
 * no publish/draft state and no student-facing path at all, ever.
 */
class LessonTutorGuidePolicy
{
    public function view(User $user, LessonTutorGuide $lessonTutorGuide): bool
    {
        return $user->hasAnyRole([UserRole::Teacher->value, UserRole::Admin->value]);
    }

    public function update(User $user, LessonTutorGuide $lessonTutorGuide): bool
    {
        return $user->hasAnyRole([UserRole::Teacher->value, UserRole::Admin->value]);
    }
}
