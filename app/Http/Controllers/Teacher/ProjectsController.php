<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ProjectSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectFeedbackRequest;
use App\Http\Resources\ProjectSubmissionResource;
use App\Models\ProjectFeedback;
use App\Models\ProjectSubmission;
use App\Notifications\ProjectFeedbackSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * The Projects tab's index rendering now lives in TeachingController —
 * this controller keeps only the JSON detail fetch and the feedback
 * action, which are unrelated to which page hosts the index.
 */
class ProjectsController extends Controller
{
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
