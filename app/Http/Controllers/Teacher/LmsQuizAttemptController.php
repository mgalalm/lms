<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LmsQuizAttemptController extends Controller
{
    /**
     * Grade a teacher's certification-quiz attempt for a course. A passing
     * score certifies the teacher for the course — the gate ClassSessionForm
     * checks before letting an admin assign them to teach it.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $quiz = $course->quiz()->with('questions')->firstOrFail();
        $teacherProfile = $request->user()->teacherProfile;

        abort_unless($teacherProfile->hasCompletedAllLessonsForCourse($course), 403);

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'integer'],
        ]);

        $totalQuestions = $quiz->questions->count();
        $correctAnswers = $quiz->questions
            ->filter(fn (QuizQuestion $question) => ($data['answers'][$question->id] ?? null) === $question->correct_option)
            ->count();

        $score = $totalQuestions > 0 ? (int) round($correctAnswers / $totalQuestions * 100) : 0;
        $passed = $score >= $quiz->passing_score;

        $teacherProfile->courseCompletions()->updateOrCreate(
            ['course_id' => $course->id],
            $passed ? ['completed_at' => now(), 'quiz_passed' => true] : ['quiz_passed' => false],
        );

        return back()->with('quizResult', ['score' => $score, 'passed' => $passed]);
    }
}
