<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LmsLessonCompletionController extends Controller
{
    /**
     * Mark a lesson as completed by the authenticated teacher, as a
     * learner — one step toward the course's certification gate.
     *
     * Completion is earned, not self-declared: the lesson must have a
     * published quiz and the teacher must have passed it. Enforced here
     * rather than only in the UI, since this is what ultimately decides
     * who may be assigned to teach a course.
     */
    public function store(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->module->course_id === $course->id, 404);

        abort_unless($request->user()->teacherProfile->canCompleteLesson($lesson), 403);

        $request->user()->teacherProfile->lessonCompletions()->firstOrCreate(
            ['lesson_id' => $lesson->id],
            ['completed_at' => now()],
        );

        return back();
    }
}
