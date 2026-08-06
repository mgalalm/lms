<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\SessionReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SessionFeedbackController extends Controller
{
    /**
     * Record the student's post-session star rating. Shares the
     * session_reviews table with the admin's QA review — only the
     * star_rating/sentiment/student_submitted_at columns are touched here,
     * so an existing QA review's category scores and flag are untouched.
     */
    public function store(Request $request, ClassSession $classSession): RedirectResponse
    {
        abort_unless($classSession->student_id === $request->user()->id, 403);

        $data = $request->validate([
            'star_rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        SessionReview::updateOrCreate(
            ['class_session_id' => $classSession->id],
            [
                'star_rating' => $data['star_rating'],
                'sentiment' => SessionReview::sentimentForRating($data['star_rating']),
                'student_submitted_at' => now(),
            ],
        );

        return back()->with('success', 'Session Feedback Submitted');
    }
}
