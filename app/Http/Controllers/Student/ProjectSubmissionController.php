<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectSubmissionRequest;
use App\Models\LessonProject;
use Illuminate\Http\RedirectResponse;

class ProjectSubmissionController extends Controller
{
    /**
     * Submit a new attempt at a lesson's project. Always creates a new
     * row rather than updating a prior attempt — a student's full
     * submission history is kept, each with its own independent
     * feedback.
     */
    public function store(StoreProjectSubmissionRequest $request, LessonProject $lessonProject): RedirectResponse
    {
        $validated = $request->validated();

        $submission = $lessonProject->submissions()->create([
            'student_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'live_url' => $validated['live_url'] ?? null,
            'submitted_at' => now(),
        ]);

        foreach ($request->file('screenshots', []) as $screenshot) {
            $submission->addMedia($screenshot)->toMediaCollection('screenshots');
        }

        return back()->with('success', 'Project submitted');
    }
}
