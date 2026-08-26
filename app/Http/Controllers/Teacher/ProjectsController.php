<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ProjectSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectFeedbackRequest;
use App\Http\Resources\ProjectSubmissionResource;
use App\Models\ClassSession;
use App\Models\FeedbackTemplate;
use App\Models\ProjectFeedback;
use App\Models\ProjectSubmission;
use App\Notifications\ProjectFeedbackSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectsController extends Controller
{
    /**
     * The teacher's project review queue — scoped to students they're
     * actually assigned to (have at least one ClassSession with), the
     * same rule ProjectSubmissionPolicy::review() and
     * Teacher\InsightsController both use.
     */
    public function index(Request $request): Response
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

        return Inertia::render('teacher/Projects', [
            'submissions' => ProjectSubmissionResource::collection($submissions)->response()->getData(true),
            'status' => $status->value,
            'counts' => ['pending' => $pendingCount, 'reviewed' => $reviewedCount],
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'search' => $search, 'featured' => $featuredOnly],
            'feedbackTemplates' => FeedbackTemplate::active()->get(['id', 'label', 'body', 'sentiment']),
        ]);
    }

    /**
     * Full submission detail (all screenshots, description, existing
     * feedback) — fetched on demand when a card is opened, to keep the
     * index payload light.
     */
    public function show(ProjectSubmission $projectSubmission): ProjectSubmissionResource
    {
        Gate::authorize('view', $projectSubmission);

        return new ProjectSubmissionResource(
            $projectSubmission->load(['lessonProject.lesson.module.course', 'student.studentProfile', 'feedback.teacher'])
        );
    }

    /**
     * Record feedback on a submission — moves it to Reviewed and notifies
     * the student. One feedback record per submission (updateOrCreate),
     * so a teacher can revise their feedback later if needed.
     */
    public function feedback(StoreProjectFeedbackRequest $request, ProjectSubmission $projectSubmission): RedirectResponse
    {
        $validated = $request->validated();

        ProjectFeedback::updateOrCreate(
            ['project_submission_id' => $projectSubmission->id],
            [
                'teacher_id' => $request->user()->id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
                'is_featured' => $validated['is_featured'] ?? false,
                'reviewed_at' => now(),
            ],
        );

        $projectSubmission->update(['status' => ProjectSubmissionStatus::Reviewed]);

        $projectSubmission->student->notify(new ProjectFeedbackSubmitted($projectSubmission));

        return back()->with('success', 'Feedback submitted');
    }
}
