<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ClassSession;
use App\Models\LessonProject;
use App\Models\ProjectSubmission;
use App\Models\User;

class ProjectSubmissionPolicy
{
    /**
     * Determine whether the user can view this submission — the student
     * who made it, a teacher assigned to that student, or an admin.
     */
    public function view(User $user, ProjectSubmission $submission): bool
    {
        return $user->id === $submission->student_id
            || $this->isAssignedTeacher($user, $submission)
            || $user->hasRole(UserRole::Admin->value);
    }

    /**
     * Determine whether the user can submit an attempt at this project.
     * Any student may submit — there's no per-student eligibility check
     * beyond the project existing at all, which routing on
     * {lessonProject} already guarantees (no project, no route match).
     */
    public function create(User $user, LessonProject $lessonProject): bool
    {
        return $user->hasRole(UserRole::Student->value);
    }

    /**
     * Determine whether the user can leave feedback on this submission —
     * a teacher assigned to the submitting student, or an admin. "Assigned"
     * here means the same thing it does everywhere else in this codebase
     * (see Teacher\InsightsController): the teacher has at least one
     * ClassSession with this student. There is no separate roster table.
     */
    public function review(User $user, ProjectSubmission $submission): bool
    {
        return $this->isAssignedTeacher($user, $submission)
            || $user->hasRole(UserRole::Admin->value);
    }

    private function isAssignedTeacher(User $user, ProjectSubmission $submission): bool
    {
        return $user->hasRole(UserRole::Teacher->value)
            && ClassSession::where('teacher_id', $user->id)
                ->where('student_id', $submission->student_id)
                ->exists();
    }
}
