<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Http\Resources\BadgeResource;
use App\Http\Resources\ClassSessionResource;
use App\Http\Resources\LeaderboardEntryResource;
use App\Models\SessionReview;
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

        $pendingFeedbackSession = $user->sessionsAsStudent()
            ->where('status', SessionStatus::Completed)
            ->whereDoesntHave('review', fn ($query) => $query->whereNotNull('student_submitted_at'))
            ->with(['lesson.module.course', 'teacher.teacherProfile'])
            ->latest('scheduled_at')
            ->first();

        $latestReview = SessionReview::whereNotNull('student_submitted_at')
            ->whereHas('classSession', fn ($query) => $query->where('student_id', $user->id))
            ->with(['classSession.lesson', 'classSession.teacher'])
            ->latest('student_submitted_at')
            ->first();

        return Inertia::render('dashboard/Student', [
            'nextSession' => $nextSession ? new ClassSessionResource($nextSession) : null,
            'pendingFeedbackSession' => $pendingFeedbackSession ? new ClassSessionResource($pendingFeedbackSession) : null,
            'latestReview' => $latestReview ? [
                'star_rating' => $latestReview->star_rating,
                'sentiment' => $latestReview->sentiment,
                'lesson_title' => $latestReview->classSession->lesson?->title,
                'teacher_name' => $latestReview->classSession->teacher?->name,
                'submitted_at' => $latestReview->student_submitted_at->toIso8601String(),
            ] : null,
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
