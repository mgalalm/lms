<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LessonSlideshow;
use App\Models\User;

/**
 * Admin-authored slideshows — teacher/admin prep material, not shown to
 * students. Unlike LessonVideo there's no draft/published state in this
 * model — readiness is just google_slides_id being set, a content concern
 * handled where it's exposed (LmsCourseDetailResource), not an
 * authorization one. Only admins can set/replace one, matching the fact
 * that only the admin role can reach Filament at all.
 */
class LessonSlideshowPolicy
{
    public function view(User $user, LessonSlideshow $lessonSlideshow): bool
    {
        return $user->hasAnyRole([UserRole::Teacher->value, UserRole::Admin->value]);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin->value);
    }

    public function update(User $user, LessonSlideshow $lessonSlideshow): bool
    {
        return $user->hasRole(UserRole::Admin->value);
    }
}
