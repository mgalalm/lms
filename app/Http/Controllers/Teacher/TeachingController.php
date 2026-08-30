<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ProjectSubmissionStatus;
use App\Enums\SupportTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClassSessionResource;
use App\Http\Resources\ProjectSubmissionResource;
use App\Http\Resources\SupportTicketResource;
use App\Http\Resources\TeacherTaskResource;
use App\Models\ClassSession;
use App\Models\FeedbackTemplate;
use App\Models\ProjectSubmission;
use App\Models\SupportTicket;
use App\Models\TeacherProfile;
use App\Models\TeacherTask;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The teacher-facing /teaching page — a single urgency-grouped feed of
 * sessions, projects awaiting review, tasks, and open support tickets,
 * rather than separate tabbed views. Everything a teacher needs to act on
 * lives at one route; type "chips" filter the already-loaded feed
 * client-side instead of navigating. Already-handled items (past
 * sessions, reviewed projects, completed tasks, resolved tickets) aren't
 * part of this feed at all — see archive() for those.
 */
class TeachingController extends Controller
{
    /**
     * Fixed group order — groups with no items are simply absent from the
     * response, never sent as an empty header.
     *
     * @var array<int, string>
     */
    private const GROUP_ORDER = ['needs_attention', 'today', 'awaiting_review', 'this_week'];

    private const GROUP_LABELS = [
        'needs_attention' => 'Needs Attention',
        'today' => 'Today',
        'awaiting_review' => 'Awaiting Your Review',
        'this_week' => 'This Week',
    ];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = $request->string('search')->value();

        $to = $request->filled('to') ? Carbon::parse($request->string('to')->value())->endOfDay() : now()->endOfDay();
        $from = $request->filled('from') ? Carbon::parse($request->string('from')->value())->startOfDay() : $to->copy()->subDays(29)->startOfDay();
        $featuredOnly = $request->boolean('featured');
        $showCompleted = $request->boolean('show_completed');

        $weekEnd = now()->addDays(7)->endOfDay();
        $overdueProjectCutoff = now()->subDays((int) config('teaching.project_review_overdue_days'));

        $sessions = $user->sessionsAsTeacher()
            ->with(['lesson.module.course', 'student.studentProfile', 'latestSupportTicket'])
            ->upcoming()
            ->where('scheduled_at', '<=', $weekEnd)
            ->get();

        $assignedStudentIds = ClassSession::where('teacher_id', $user->id)->distinct()->pluck('student_id');

        $projects = ProjectSubmission::query()
            ->whereIn('student_id', $assignedStudentIds)
            ->where('status', ProjectSubmissionStatus::Submitted)
            ->whereBetween('submitted_at', [$from, $to])
            ->when($featuredOnly, fn ($query) => $query->whereHas('feedback', fn ($q) => $q->where('is_featured', true)))
            ->with(['lessonProject.lesson.module.course', 'student.studentProfile', 'feedback.teacher'])
            ->get();

        $openTickets = SupportTicket::where('teacher_id', $user->id)
            ->where('status', SupportTicketStatus::Open)
            ->with('classSession.lesson')
            ->get();

        $tasks = TeacherTask::where('teacher_id', $user->id)
            ->with('lesson.module.course')
            ->when(! $showCompleted, fn ($query) => $query->pending())
            ->where(function ($query) use ($weekEnd) {
                // Pending + overdue tasks stay visible no matter how old —
                // they still need attention. Everything else (due soon, or
                // completed when show_completed is on) is bounded to a
                // recent window — without this, "due_at <= $weekEnd" would
                // trivially match every completed task ever, since any
                // past date is "before" a future cutoff.
                $query->where(fn ($q) => $q->whereNull('completed_at')->where('due_at', '<', now()))
                    ->orWhereBetween('due_at', [now()->subDays(7), $weekEnd]);
            })
            ->get();

        // Intermediate items keep the raw model for search matching —
        // student.name is encrypted, so it can't be matched with a SQL
        // LIKE (same technique as Teacher\InsightsController); these
        // sources are already bounded to a small "active backlog" window,
        // so filtering in PHP after decryption is safe.
        $items = collect()
            ->concat($sessions->map(fn (ClassSession $session) => [
                'type' => 'session',
                'model' => $session,
                'sort_at' => $session->scheduled_at,
                'is_overdue' => false,
                'group' => $session->scheduled_at->isToday() ? 'today' : 'this_week',
            ]))
            ->concat($projects->map(function (ProjectSubmission $project) use ($overdueProjectCutoff) {
                $isOverdue = $project->submitted_at->lt($overdueProjectCutoff);

                return [
                    'type' => 'project',
                    'model' => $project,
                    'sort_at' => $project->submitted_at,
                    'is_overdue' => $isOverdue,
                    'group' => $isOverdue ? 'needs_attention' : 'awaiting_review',
                ];
            }))
            ->concat($tasks->map(function (TeacherTask $task) {
                $isOverdue = $task->completed_at === null && $task->due_at->isPast();

                return [
                    'type' => 'task',
                    'model' => $task,
                    'sort_at' => $task->due_at,
                    'is_overdue' => $isOverdue,
                    'group' => $isOverdue ? 'needs_attention' : ($task->due_at->isToday() ? 'today' : 'this_week'),
                ];
            }))
            // Every open ticket is, by definition, an active issue still
            // being tracked — always Needs Attention, but not flagged
            // "overdue" (that styling implies lateness, not just openness).
            ->concat($openTickets->map(fn (SupportTicket $ticket) => [
                'type' => 'ticket',
                'model' => $ticket,
                'sort_at' => $ticket->created_at,
                'is_overdue' => false,
                'group' => 'needs_attention',
            ]));

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $items = $items->filter(fn (array $item) => str_contains($this->searchableText($item), $needle))->values();
        }

        $chipCounts = [
            'all' => $items->count(),
            'sessions' => $items->where('type', 'session')->count(),
            'projects' => $items->where('type', 'project')->count(),
            'tasks' => $items->where('type', 'task')->count(),
            'tickets' => $items->where('type', 'ticket')->count(),
        ];

        $feed = collect(self::GROUP_ORDER)
            ->map(fn (string $group) => [
                'key' => $group,
                'label' => self::GROUP_LABELS[$group],
                'items' => $items->where('group', $group)->sortBy('sort_at')->values()->map($this->toEnvelope(...)),
            ])
            ->filter(fn (array $group) => $group['items']->isNotEmpty())
            ->values();

        return Inertia::render('teacher/Teaching', [
            'today' => now()->toDateString(),
            'timezone' => $user->teacherProfile?->resolvedTimezone() ?? TeacherProfile::DEFAULT_TIMEZONE,
            'summary' => [
                'overdue' => $items->where('group', 'needs_attention')->count(),
                'sessionsToday' => $items->where('type', 'session')->where('group', 'today')->count(),
                'projectsAwaitingReview' => $items->where('type', 'project')->where('group', 'awaiting_review')->count(),
            ],
            'chipCounts' => $chipCounts,
            'feed' => $feed,
            'filters' => [
                'search' => $search,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'featured' => $featuredOnly,
                'show_completed' => $showCompleted,
            ],
            'feedbackTemplates' => FeedbackTemplate::active()->get(['id', 'label', 'body', 'sentiment']),
        ]);
    }

    /**
     * A lightweight, read-only view of already-handled items that don't
     * belong in the priority feed — past sessions, reviewed projects,
     * completed tasks, and resolved support tickets. Deliberately
     * minimal: no filter toolbar, just paginated lists.
     */
    public function archive(Request $request): Response
    {
        $user = $request->user();

        $pastSessions = $user->sessionsAsTeacher()
            ->with(['lesson.module.course', 'student.studentProfile'])
            ->past()
            ->paginate(10, ['*'], 'sessions_page')
            ->withQueryString();

        $assignedStudentIds = ClassSession::where('teacher_id', $user->id)->distinct()->pluck('student_id');

        $reviewedProjects = ProjectSubmission::query()
            ->whereIn('student_id', $assignedStudentIds)
            ->where('status', ProjectSubmissionStatus::Reviewed)
            ->with(['lessonProject.lesson.module.course', 'student.studentProfile', 'feedback.teacher'])
            ->orderByDesc('submitted_at')
            ->paginate(12, ['*'], 'projects_page')
            ->withQueryString();

        $completedTasks = TeacherTask::where('teacher_id', $user->id)
            ->whereNotNull('completed_at')
            ->with('lesson')
            ->orderByDesc('completed_at')
            ->paginate(15, ['*'], 'tasks_page')
            ->withQueryString();

        $resolvedTickets = SupportTicket::where('teacher_id', $user->id)
            ->where('status', SupportTicketStatus::Resolved)
            ->with('classSession.lesson')
            ->orderByDesc('resolved_at')
            ->paginate(15, ['*'], 'tickets_page')
            ->withQueryString();

        return Inertia::render('teacher/TeachingArchive', [
            'pastSessions' => ClassSessionResource::collection($pastSessions)->response()->getData(true),
            'reviewedProjects' => ProjectSubmissionResource::collection($reviewedProjects)->response()->getData(true),
            'completedTasks' => TeacherTaskResource::collection($completedTasks)->response()->getData(true),
            'resolvedTickets' => SupportTicketResource::collection($resolvedTickets)->response()->getData(true),
            'feedbackTemplates' => FeedbackTemplate::active()->get(['id', 'label', 'body', 'sentiment']),
        ]);
    }

    private function toEnvelope(array $item): array
    {
        return [
            'id' => "{$item['type']}-{$item['model']->id}",
            'type' => $item['type'],
            'is_overdue' => $item['is_overdue'],
            'at' => $item['sort_at']->toIso8601String(),
            'session' => $item['type'] === 'session' ? new ClassSessionResource($item['model']) : null,
            'project' => $item['type'] === 'project' ? new ProjectSubmissionResource($item['model']) : null,
            'task' => $item['type'] === 'task' ? new TeacherTaskResource($item['model']) : null,
            'ticket' => $item['type'] === 'ticket' ? new SupportTicketResource($item['model']) : null,
        ];
    }

    private function searchableText(array $item): string
    {
        $model = $item['model'];

        $parts = match ($item['type']) {
            'session' => [
                $model->lesson->title,
                $model->student->name,
                $model->student->studentProfile?->code,
            ],
            'project' => [
                $model->title,
                $model->lessonProject?->lesson?->title,
                $model->student->name,
                $model->student->studentProfile?->code,
            ],
            'task' => [
                $model->title,
                $model->description,
                $model->lesson?->title,
            ],
            'ticket' => [
                $model->subject,
                $model->description,
                $model->code,
                $model->classSession?->lesson?->title,
            ],
        };

        return mb_strtolower(collect($parts)->filter()->join(' '));
    }
}
