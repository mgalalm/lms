<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ProjectSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClassSessionResource;
use App\Http\Resources\ProjectSubmissionResource;
use App\Http\Resources\TeacherTaskResource;
use App\Models\ClassSession;
use App\Models\FeedbackTemplate;
use App\Models\ProjectSubmission;
use App\Models\TeacherProfile;
use App\Models\TeacherTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The teacher-facing /teaching page — Sessions Schedule, Projects, and
 * Tasks as tabs of one page rather than three standalone routes, so
 * teachers have a single place to see what needs their attention. Each
 * tab is still its own real route (deep-linkable, working back button);
 * only the active tab's data is fetched per request.
 */
class TeachingController extends Controller
{
    /**
     * Sessions Schedule tab. Relocated from the now-deleted
     * SessionsScheduleController — same query, same behavior.
     */
    public function sessions(Request $request): Response
    {
        $filter = $request->string('filter', 'upcoming')->value();

        $query = $request->user()->sessionsAsTeacher()
            ->with(['lesson.module.course', 'student.studentProfile', 'latestSupportTicket']);

        $sessions = (match ($filter) {
            'today' => $query->today()->orderBy('scheduled_at'),
            'past' => $query->past(),
            default => $query->upcoming(),
        })->paginate(15)->withQueryString();

        return Inertia::render('teacher/Teaching', [
            ...$this->headerProps($request->user()),
            ...$this->tabCounts($request->user()),
            'activeTab' => 'sessions',
            'sessions' => ClassSessionResource::collection($sessions)->response()->getData(true),
            'filter' => $filter,
        ]);
    }

    /**
     * Projects tab. Relocated from Teacher\ProjectsController::index()
     * (that controller keeps show()/feedback() — those are unrelated to
     * which page hosts the index).
     */
    public function projects(Request $request): Response
    {
        $user = $request->user();
        $status = $request->string('status', 'pending')->value() === 'reviewed'
            ? ProjectSubmissionStatus::Reviewed
            : ProjectSubmissionStatus::Submitted;

        $to = $request->filled('to') ? Carbon::parse($request->string('to')->value())->endOfDay() : now()->endOfDay();
        $from = $request->filled('from') ? Carbon::parse($request->string('from')->value())->startOfDay() : $to->copy()->subDays(29)->startOfDay();

        $search = $request->string('search')->value();
        $featuredOnly = $request->boolean('featured');

        $assignedStudentIds = ClassSession::where('teacher_id', $user->id)->distinct()->pluck('student_id');

        $baseQuery = ProjectSubmission::query()
            ->whereIn('student_id', $assignedStudentIds)
            ->whereBetween('submitted_at', [$from, $to])
            ->when($featuredOnly, fn ($query) => $query->whereHas('feedback', fn ($q) => $q->where('is_featured', true)))
            ->with(['lessonProject.lesson.module.course', 'student.studentProfile', 'feedback.teacher']);

        $pendingCount = (clone $baseQuery)->where('status', ProjectSubmissionStatus::Submitted)->count();
        $reviewedCount = (clone $baseQuery)->where('status', ProjectSubmissionStatus::Reviewed)->count();

        $statusQuery = (clone $baseQuery)->where('status', $status)->orderByDesc('submitted_at');

        if ($search === '') {
            $submissions = $statusQuery->paginate(12)->withQueryString();
        } else {
            // student.name is encrypted, so it can't be matched with a SQL
            // LIKE — same technique as Teacher\InsightsController: this
            // query is already scoped to one teacher's assigned students
            // within a date range (a small, bounded set), so filtering the
            // fetched results in PHP after decryption is safe.
            $needle = mb_strtolower($search);

            $matches = $statusQuery->get()
                ->filter(fn (ProjectSubmission $submission) => str_contains(mb_strtolower($submission->student->name), $needle)
                    || str_contains(mb_strtolower($submission->student->studentProfile?->code ?? ''), $needle)
                    || str_contains(mb_strtolower($submission->title), $needle))
                ->values();

            $page = LengthAwarePaginator::resolveCurrentPage();
            $perPage = 12;

            $submissions = new LengthAwarePaginator(
                $matches->forPage($page, $perPage)->values(),
                $matches->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()],
            );
        }

        return Inertia::render('teacher/Teaching', [
            ...$this->headerProps($user),
            ...$this->tabCounts($user),
            'activeTab' => 'projects',
            'submissions' => ProjectSubmissionResource::collection($submissions)->response()->getData(true),
            'status' => $status->value,
            'projectCounts' => ['pending' => $pendingCount, 'reviewed' => $reviewedCount],
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'search' => $search, 'featured' => $featuredOnly],
            'feedbackTemplates' => FeedbackTemplate::active()->get(['id', 'label', 'body', 'sentiment']),
        ]);
    }

    /**
     * Tasks tab.
     */
    public function tasks(Request $request): Response
    {
        $user = $request->user();
        $showCompleted = $request->boolean('show_completed');

        $query = TeacherTask::where('teacher_id', $user->id)
            ->with('lesson')
            ->when(! $showCompleted, fn ($query) => $query->pending())
            ->orderByRaw('(completed_at IS NULL AND due_at < ?) DESC', [now()->toDateTimeString()])
            ->orderBy('due_at');

        $tasks = $query->paginate(15)->withQueryString();

        return Inertia::render('teacher/Teaching', [
            ...$this->headerProps($user),
            ...$this->tabCounts($user),
            'activeTab' => 'tasks',
            'tasks' => TeacherTaskResource::collection($tasks)->response()->getData(true),
            'showCompleted' => $showCompleted,
        ]);
    }

    /**
     * @return array{today: string, nextSession: ?ClassSessionResource, timezone: string}
     */
    private function headerProps(User $teacher): array
    {
        $nextSession = $teacher->sessionsAsTeacher()
            ->with(['lesson.module.course', 'student.studentProfile'])
            ->currentOrUpcoming()
            ->first();

        return [
            'today' => now()->toDateString(),
            'nextSession' => $nextSession ? new ClassSessionResource($nextSession) : null,
            'timezone' => $teacher->teacherProfile?->resolvedTimezone() ?? TeacherProfile::DEFAULT_TIMEZONE,
        ];
    }

    /**
     * @return array{tabCounts: array{pendingProjects: int, pendingTasks: int}}
     */
    private function tabCounts(User $teacher): array
    {
        $assignedStudentIds = ClassSession::where('teacher_id', $teacher->id)->distinct()->pluck('student_id');

        return [
            'tabCounts' => [
                'pendingProjects' => ProjectSubmission::whereIn('student_id', $assignedStudentIds)
                    ->where('status', ProjectSubmissionStatus::Submitted)
                    ->count(),
                'pendingTasks' => TeacherTask::where('teacher_id', $teacher->id)->pending()->count(),
            ],
        ];
    }
}
