<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\TeacherTaskType;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonSlideshow;
use App\Models\TeacherTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LessonSlideshowViewedController extends Controller
{
    /**
     * Fired once when a teacher expands a lesson's slideshow — there's no
     * other backend hit on view since the Google Slides embed is a plain
     * client-side iframe (see LessonSlideshowViewer.vue), so this exists
     * purely to resolve the lesson's pending session_prep task(s).
     */
    public function __invoke(Request $request, Lesson $lesson): RedirectResponse
    {
        $slideshow = $lesson->slideshow;

        Gate::authorize('view', $slideshow ?? LessonSlideshow::make(['lesson_id' => $lesson->id]));

        TeacherTask::where('teacher_id', $request->user()->id)
            ->where('lesson_id', $lesson->id)
            ->where('type', TeacherTaskType::SessionPrep)
            ->whereNull('completed_at')
            ->update(['completed_at' => now()]);

        return back();
    }
}
