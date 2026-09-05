<?php

namespace App\Policies;

use App\Enums\LessonQuizStatus;
use App\Enums\UserRole;
use App\Models\LessonQuiz;
use App\Models\User;

/**
 * Students may only ever view/attempt a published lesson quiz — draft
 * quizzes (including ones an admin is still mid-edit on) are admin-only.
 * Enforced here rather than just hidden in the UI: both DashboardController
 * (what gets serialized to the student) and LessonQuizAttemptController
 * (submitting answers) go through this check.
 */
class LessonQuizPolicy
{
    public function view(User $user, LessonQuiz $lessonQuiz): bool
    {
        return $lessonQuiz->status === LessonQuizStatus::Published
            || $user->hasRole(UserRole::Admin->value);
    }
}
