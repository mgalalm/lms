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
     * learner. Self-declared on purpose: the assessment happens at the
     * module quiz, which completing every lesson in a module unlocks.
     */
    public function store(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->module->course_id === $course->id, 404);

        $request->user()->teacherProfile->lessonCompletions()->firstOrCreate(
            ['lesson_id' => $lesson->id],
            ['completed_at' => now()],
        );

        return back();
    }
}
