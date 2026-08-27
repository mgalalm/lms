<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeacherTaskController extends Controller
{
    /**
     * Mark a manual or session_review task complete. session_prep tasks
     * auto-complete elsewhere (LessonTutorGuideController,
     * LessonSlideshowViewedController) — nothing stops one being marked
     * complete here too, but the Tasks tab UI doesn't offer the button
     * for that type.
     */
    public function complete(Request $request, TeacherTask $teacherTask): RedirectResponse
    {
        abort_unless($teacherTask->teacher_id === $request->user()->id, 403);

        if (! $teacherTask->completed_at) {
            $teacherTask->update(['completed_at' => now()]);
        }

        return back();
    }
}
