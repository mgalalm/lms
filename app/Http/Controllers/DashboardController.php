<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Resources\ClassSessionResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->hasRole(UserRole::Student->value)) {
            return $this->student($user);
        }

        if ($user->hasRole(UserRole::Teacher->value)) {
            return $this->teacher($user);
        }

        return Inertia::render('Dashboard');
    }

    private function student(User $user): Response
    {
        $nextSession = $user->sessionsAsStudent()
            ->with(['lesson.module.course', 'teacher.teacherProfile'])
            ->upcoming()
            ->first();

        return Inertia::render('dashboard/Student', [
            'nextSession' => $nextSession ? new ClassSessionResource($nextSession) : null,
        ]);
    }

    private function teacher(User $user): Response
    {
        $nextSession = $user->sessionsAsTeacher()
            ->with(['lesson.module.course', 'student.studentProfile'])
            ->upcoming()
            ->first();

        return Inertia::render('dashboard/Teacher', [
            'nextSession' => $nextSession ? new ClassSessionResource($nextSession) : null,
        ]);
    }
}
