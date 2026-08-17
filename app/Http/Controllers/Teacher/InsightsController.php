<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\FlagSeverity;
use App\Enums\SessionStatus;
use App\Enums\SessionType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewedClassSessionResource;
use App\Models\ClassSession;
use App\Models\SessionReview;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class InsightsController extends Controller
{
    /**
     * How late a teacher can join/check in to a session and still count
     * as "on time".
     */
    private const GRACE_MINUTES = 5;

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $teacherProfile = $user->teacherProfile;

        $to = $request->filled('to') ? Carbon::parse($request->string('to')->value())->endOfDay() : now()->endOfDay();
        $from = $request->filled('from') ? Carbon::parse($request->string('from')->value())->startOfDay() : $to->copy()->subDays(29)->startOfDay();

        $flagFilter = $request->string('flag', 'all')->value();
        $search = $request->string('search')->value();

        $periodSessions = ClassSession::where('teacher_id', $user->id)
            ->where('status', SessionStatus::Completed)
            ->whereBetween('scheduled_at', [$from, $to])
            ->get(['id', 'type', 'scheduled_at', 'teacher_joined_at']);

        $periodReviews = SessionReview::whereHas(
            'classSession',
            fn ($query) => $query->where('teacher_id', $user->id)->whereBetween('scheduled_at', [$from, $to])
        )->get();

        $metrics = $teacherProfile->insightMetrics()
            ->where('period_start', '<=', $to)
            ->where('period_end', '>=', $from)
            ->get();

        $reviewedSessionsQuery = ClassSession::where('teacher_id', $user->id)
            ->whereHas('review')
            ->whereBetween('scheduled_at', [$from, $to])
            ->with(['review', 'lesson', 'student.studentProfile']);

        if ($flagFilter !== 'all') {
            $reviewedSessionsQuery->whereHas('review', fn ($query) => $query->where('flag_severity', $flagFilter));
        }

        if ($search === '') {
            $reviewedSessions = $reviewedSessionsQuery->orderByDesc('scheduled_at')->paginate(15)->withQueryString();
        } else {
            // student.name is encrypted, so it can't be matched with a SQL
            // LIKE. This query is already scoped to one teacher's own
            // sessions within the selected date range — a small, bounded
            // set — so filtering the fetched results in PHP (after
            // decryption) is safe and keeps name search working.
            $needle = mb_strtolower($search);

            $matches = $reviewedSessionsQuery->get()
                ->filter(fn (ClassSession $session) => str_contains(mb_strtolower($session->student->name), $needle)
                    || str_contains(mb_strtolower($session->student->studentProfile?->code ?? ''), $needle)
                    || str_contains(mb_strtolower($session->lesson?->title ?? ''), $needle))
                ->sortByDesc('scheduled_at')
                ->values();

            $page = LengthAwarePaginator::resolveCurrentPage();
            $perPage = 15;

            $reviewedSessions = new LengthAwarePaginator(
                $matches->forPage($page, $perPage)->values(),
                $matches->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()],
            );
        }

        return Inertia::render('teacher/Insights', [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'flag' => $flagFilter,
            'search' => $search,
            'profile' => [
                'sessions_taught' => ClassSession::where('teacher_id', $user->id)->where('status', SessionStatus::Completed)->count(),
                'certificates_earned' => $teacherProfile->courseCompletions()->where('quiz_passed', true)->whereNotNull('completed_at')->count(),
                'points' => $user->totalPoints(),
            ],
            'metrics' => [
                'objections' => $metrics->sum('objections'),
                'excuses' => $metrics->sum('excuses'),
                'study_level' => $metrics->isNotEmpty() ? (int) round($metrics->avg('study_level')) : 0,
            ],
            'attendance' => $this->attendanceSummary($periodSessions),
            'performance' => $this->performanceSummary($periodReviews),
            'flagsBreakdown' => [
                'green' => $periodReviews->where('flag_severity', FlagSeverity::Green)->count(),
                'yellow' => $periodReviews->where('flag_severity', FlagSeverity::Yellow)->count(),
                'red' => $periodReviews->where('flag_severity', FlagSeverity::Red)->count(),
            ],
            'reviewedSessions' => ReviewedClassSessionResource::collection($reviewedSessions)->response()->getData(true),
        ]);
    }

    /**
     * @param  Collection<int, ClassSession>  $sessions
     * @return array<string, array{on_time: int, late: int}>
     */
    private function attendanceSummary(Collection $sessions): array
    {
        $summary = fn (SessionType $type) => $sessions->where('type', $type)
            ->filter(fn (ClassSession $session) => $session->teacher_joined_at !== null)
            ->reduce(function (array $carry, ClassSession $session) {
                $onTime = $session->teacher_joined_at->lessThanOrEqualTo($session->scheduled_at->copy()->addMinutes(self::GRACE_MINUTES));
                $carry[$onTime ? 'on_time' : 'late']++;

                return $carry;
            }, ['on_time' => 0, 'late' => 0]);

        return [
            'check_in' => $summary(SessionType::Physical),
            'join' => $summary(SessionType::Online),
        ];
    }

    /**
     * @param  Collection<int, SessionReview>  $reviews
     * @return array{categories: array<string, int>, total_score: int}
     */
    private function performanceSummary(Collection $reviews): array
    {
        $categories = collect(SessionReview::CATEGORIES)->mapWithKeys(
            fn (string $category) => [$category => $reviews->isNotEmpty() ? (int) round($reviews->avg($category)) : 0]
        );

        return [
            'categories' => $categories->toArray(),
            'total_score' => $categories->isNotEmpty() ? (int) round($categories->avg()) : 0,
        ];
    }
}
