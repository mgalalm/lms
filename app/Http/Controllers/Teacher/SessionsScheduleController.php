<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassSessionResource;
use App\Models\TeacherProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionsScheduleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $filter = $request->string('filter', 'upcoming')->value();

        $query = $request->user()->sessionsAsTeacher()
            ->with(['lesson.module.course', 'student.studentProfile', 'latestSupportTicket']);

        $sessions = (match ($filter) {
            'today' => $query->today()->orderBy('scheduled_at'),
            'past' => $query->past(),
            default => $query->upcoming(),
        })->paginate(15)->withQueryString();

        return Inertia::render('teacher/SessionsSchedule', [
            'sessions' => ClassSessionResource::collection($sessions)->response()->getData(true),
            'filter' => $filter,
            'timezone' => $request->user()->teacherProfile?->resolvedTimezone() ?? TeacherProfile::DEFAULT_TIMEZONE,
        ]);
    }
}
