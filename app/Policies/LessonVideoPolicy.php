<?php

namespace App\Policies;

use App\Enums\LessonVideoStatus;
use App\Enums\UserRole;
use App\Models\LessonVideo;
use App\Models\User;

class LessonVideoPolicy
{
    /**
     * Published videos are visible to anyone; unpublished (uploading,
     * processing, or draft) videos are only visible to teachers and admins
     * reviewing curriculum content before it goes live.
     */
    public function view(?User $user, LessonVideo $lessonVideo): bool
    {
        if ($lessonVideo->status === LessonVideoStatus::Published) {
            return true;
        }

        return $user !== null && $user->hasAnyRole([UserRole::Teacher->value, UserRole::Admin->value]);
    }
}
