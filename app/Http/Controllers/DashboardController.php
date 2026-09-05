<?php

namespace App\Http\Controllers;

use App\Enums\LessonVideoStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Http\Resources\BadgeResource;
use App\Http\Resources\ClassSessionResource;
use App\Http\Resources\LeaderboardEntryResource;
use App\Http\Resources\LessonQuizResource;
use App\Http\Resources\ProjectSubmissionResource;
use App\Models\ClassSession;
use App\Models\Lesson;
use App\Models\SessionReview;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        // Admins live in the Filament back office, not the Inertia
        // portals — send them straight there instead of a generic
        // dashboard with nothing on it. Catches every path that lands
        // here (login, email verification, direct navigation), not just
        // the login form.
        if ($user->hasRole(UserRole::Admin->value)) {
            return redirect()->route('filament.admin.pages.dashboard');
        }

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
            ->currentOrUpcoming()
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

        $currentSession = $nextSession
            ?? $pendingFeedbackSession
            ?? $user->sessionsAsStudent()->latest('scheduled_at')->first();

        $currentLesson = $currentSession?->lesson;

        if ($currentLesson) {
            $currentLesson->loadMissing('module.course', 'video', 'project', 'quiz.questions');
        }

        $visibleQuiz = $currentLesson?->quiz && $user->can('view', $currentLesson->quiz)
            ? $currentLesson->quiz
            : null;

        $projectSubmissions = $currentLesson?->project
            ? $currentLesson->project->submissions()
                ->where('student_id', $user->id)
                ->with('feedback.teacher')
                ->latest('submitted_at')
                ->get()
            : collect();

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
            'currentLesson' => $currentLesson ? [
                'id' => $currentLesson->id,
                'title' => $currentLesson->title,
                'reading_content' => $currentLesson->reading_content,
                'module_title' => $currentLesson->module?->title,
                'course_title' => $currentLesson->module?->course?->name,
                'video' => $currentLesson->video?->status === LessonVideoStatus::Published
                    ? ['duration_seconds' => $currentLesson->video->duration_seconds]
                    : null,
                'project' => $currentLesson->project ? [
                    'id' => $currentLesson->project->id,
                    'title' => $currentLesson->project->title,
                    'brief' => $currentLesson->project->brief,
                    'is_required' => $currentLesson->project->is_required,
                ] : null,
                'quiz' => $visibleQuiz ? new LessonQuizResource($visibleQuiz) : null,
            ] : null,
            'projectSubmissions' => ProjectSubmissionResource::collection($projectSubmissions),
            'progress' => $currentLesson ? $this->moduleProgress($user, $currentLesson) : null,
            'points' => $user->totalPoints(),
            'recentBadges' => BadgeResource::collection(
                $user->badges()->orderByDesc('badge_user.awarded_at')->limit(3)->get()
            ),
            'leaderboard' => $this->leaderboard(UserRole::Student->value, $user->id),
            'timezone' => $user->studentProfile?->resolvedTimezone() ?? StudentProfile::DEFAULT_TIMEZONE,
        ]);
    }

    /**
     * How many distinct lessons in the current lesson's module the student
     * has completed a session for, out of the module's total lesson count.
     *
     * @return array{module_title: ?string, completed: int, total: int}
     */
    private function moduleProgress(User $user, Lesson $currentLesson): array
    {
        $module = $currentLesson->module;
        $total = $module?->lessons()->count() ?? 0;

        $completed = $module
            ? ClassSession::query()
                ->where('student_id', $user->id)
                ->where('status', SessionStatus::Completed)
                ->whereHas('lesson', fn ($query) => $query->where('module_id', $module->id))
                ->distinct('lesson_id')
                ->count('lesson_id')
            : 0;

        return [
            'module_title' => $module?->title,
            'completed' => $completed,
            'total' => $total,
        ];
    }

    private function teacher(User $user): Response
    {
        $nextSession = $user->sessionsAsTeacher()
            ->with(['lesson.module.course', 'student.studentProfile'])
            ->currentOrUpcoming()
            ->first();

        return Inertia::render('dashboard/Teacher', [
            'nextSession' => $nextSession ? new ClassSessionResource($nextSession) : null,
            'points' => $user->totalPoints(),
            'leaderboard' => $this->leaderboard(UserRole::Teacher->value, $user->id),
            'timezone' => $user->teacherProfile?->resolvedTimezone() ?? TeacherProfile::DEFAULT_TIMEZONE,
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
