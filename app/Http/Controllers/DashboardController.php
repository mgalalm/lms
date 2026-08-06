<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Resources\BadgeResource;
use App\Http\Resources\ClassSessionResource;
use App\Http\Resources\LeaderboardEntryResource;
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
            'points' => $user->totalPoints(),
            'recentBadges' => BadgeResource::collection(
                $user->badges()->orderByDesc('badge_user.awarded_at')->limit(3)->get()
            ),
            'leaderboard' => $this->leaderboard(UserRole::Student->value, $user->id),
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
            'points' => $user->totalPoints(),
            'leaderboard' => $this->leaderboard(UserRole::Teacher->value, $user->id),
        ]);
    }

    /**
     * @return array<int, LeaderboardEntryResource>
     */
    private function leaderboard(string $role, int $viewerId, int $limit = 5): array
    {
        return User::leaderboard($role)->limit($limit)->get()
            ->map(fn (User $user, int $index) => new LeaderboardEntryResource($user, $index + 1, $viewerId))
            ->all();
    }
}
