<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\LessonQuizStatus;
use App\Enums\SettingKey;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonQuizQuestion;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LmsLessonQuizAttemptController extends Controller
{
    /**
     * Record a teacher's attempt at a lesson quiz. Passing is what unlocks
     * marking the lesson complete (see LmsLessonCompletionController), so
     * unlike the student flow these attempts are persisted and capped.
     */
    public function store(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->module->course_id === $course->id, 404);

        $quiz = $lesson->quiz()->with('questions')->firstOrFail();

        abort_unless($quiz->status === LessonQuizStatus::Published, 403);

        $teacherProfile = $request->user()->teacherProfile;

        abort_unless($teacherProfile->canAttemptLessonQuiz($quiz), 403);

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string'],
        ]);

        $totalQuestions = $quiz->questions->count();
        $correctCount = $quiz->questions
            ->filter(fn (LessonQuizQuestion $question) => ($data['answers'][$question->id] ?? null) === $question->correct_answer)
            ->count();

        $score = $totalQuestions > 0 ? (int) round($correctCount / $totalQuestions * 100) : 0;
        $passed = $score >= (int) Setting::get(SettingKey::QuizPassingScore);

        $teacherProfile->lessonQuizAttempts()->create([
            'lesson_quiz_id' => $quiz->id,
            'score' => $score,
            'passed' => $passed,
        ]);

        // Distinct from the student flow's `lessonQuizResult` — same idea,
        // different payload (pass/fail and remaining attempts matter here).
        return back()->with('teacherLessonQuizResult', [
            'lesson_id' => $lesson->id,
            'score' => $score,
            'passed' => $passed,
            'correct' => $correctCount,
            'total' => $totalQuestions,
            'attempts_remaining' => $teacherProfile->attemptsRemainingFor($quiz),
        ]);
    }
}
