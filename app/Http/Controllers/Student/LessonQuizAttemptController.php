<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonQuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LessonQuizAttemptController extends Controller
{
    /**
     * Grade a student's attempt at a lesson quiz. Stateless — same
     * convention as Teacher\LmsQuizAttemptController: nothing is persisted
     * beyond the questions themselves, the score is transient flash data.
     */
    public function store(Request $request, Lesson $lesson): RedirectResponse
    {
        $quiz = $lesson->quiz()->with('questions')->firstOrFail();

        abort_unless($request->user()->can('view', $quiz), 403);

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string'],
        ]);

        $totalQuestions = $quiz->questions->count();
        $correctCount = $quiz->questions
            ->filter(fn (LessonQuizQuestion $question) => ($data['answers'][$question->id] ?? null) === $question->correct_answer)
            ->count();

        $score = $totalQuestions > 0 ? (int) round($correctCount / $totalQuestions * 100) : 0;

        $results = $quiz->questions->map(fn (LessonQuizQuestion $question) => [
            'id' => $question->id,
            'submitted_answer' => $data['answers'][$question->id] ?? null,
            'correct_answer' => $question->correct_answer,
            'explanation' => $question->explanation,
            'is_correct' => ($data['answers'][$question->id] ?? null) === $question->correct_answer,
        ])->values();

        return back()->with('lessonQuizResult', [
            'score' => $score,
            'correct' => $correctCount,
            'total' => $totalQuestions,
            'results' => $results,
        ]);
    }
}
