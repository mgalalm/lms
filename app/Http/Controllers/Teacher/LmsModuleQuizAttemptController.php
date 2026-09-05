<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\SettingKey;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LessonQuizQuestion;
use App\Models\Module;
use App\Models\Setting;
use App\Models\TeacherProfile;
use App\Services\ModuleQuizAssembler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LmsModuleQuizAttemptController extends Controller
{
    /**
     * Start an attempt: draw a fresh, balanced set of questions from the
     * module's lesson banks and record them. Drawing at start (rather than
     * at submit) is what lets grading score against exactly what the
     * teacher was shown.
     */
    public function start(Request $request, Course $course, Module $module, ModuleQuizAssembler $assembler): RedirectResponse
    {
        abort_unless($module->course_id === $course->id, 404);

        $teacherProfile = $request->user()->teacherProfile;

        $fingerprint = $assembler->bankFingerprint($module);

        // Resume a started-but-unsubmitted attempt rather than drawing a
        // new one — otherwise a refresh would silently reroll the
        // questions and let a teacher shop for an easier draw.
        //
        // Checked BEFORE the attempt cap on purpose: an open attempt
        // already counts against the cap, so on the final attempt the cap
        // check would otherwise refuse to let the teacher back into the
        // very attempt they're mid-way through.
        $open = $teacherProfile->moduleQuizAttempts()
            ->counting()
            ->where('module_id', $module->id)
            ->whereNull('submitted_at')
            ->first();

        // Only resume while the module still looks the way it did when the
        // draw was made. If lessons or their quizzes have changed since,
        // the stale draw is replaced in place — the teacher hasn't been
        // graded on it, and it shouldn't cost them an attempt that the
        // content change invalidated.
        if ($open && $open->bank_fingerprint === $fingerprint) {
            return back();
        }

        if (! $open) {
            abort_unless($teacherProfile->canAttemptModuleQuiz($module), 403);
        }

        $questions = $assembler->assemble($module);

        abort_if($questions->isEmpty(), 403, 'This module has no quiz questions yet.');

        $payload = [
            'module_id' => $module->id,
            'question_ids' => $questions->pluck('id')->all(),
            'bank_fingerprint' => $fingerprint,
        ];

        if ($open) {
            $open->update($payload);
        } else {
            $teacherProfile->moduleQuizAttempts()->create($payload);
        }

        return back();
    }

    /**
     * Grade the teacher's open attempt. Passing certifies them for the
     * module, which is what allows them to be assigned to teach it.
     */
    public function store(Request $request, Course $course, Module $module): RedirectResponse
    {
        abort_unless($module->course_id === $course->id, 404);

        $teacherProfile = $request->user()->teacherProfile;

        $attempt = $teacherProfile->moduleQuizAttempts()
            ->counting()
            ->where('module_id', $module->id)
            ->whereNull('submitted_at')
            ->firstOrFail();

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string'],
        ]);

        $questions = $attempt->questions();
        $total = $questions->count();

        $correct = $questions
            ->filter(fn (LessonQuizQuestion $question) => ($data['answers'][$question->id] ?? null) === $question->correct_answer)
            ->count();

        $score = $total > 0 ? (int) round($correct / $total * 100) : 0;
        $passed = $score >= (int) Setting::get(SettingKey::QuizPassingScore);

        $attempt->update([
            'score' => $score,
            'passed' => $passed,
            'submitted_at' => now(),
        ]);

        if ($passed) {
            $this->certify($teacherProfile, $module);
        }

        return back()->with('moduleQuizResult', [
            'module_id' => $module->id,
            'score' => $score,
            'passed' => $passed,
            'correct' => $correct,
            'total' => $total,
            'attempts_remaining' => $teacherProfile->attemptsRemainingFor($module),
        ]);
    }

    /**
     * Record the module certification, then roll it up: a teacher counts
     * as certified for the course once every one of its modules is passed.
     * The course-level record is what Insights and the LMS cards read.
     */
    private function certify(TeacherProfile $teacherProfile, Module $module): void
    {
        $teacherProfile->moduleCompletions()->updateOrCreate(
            ['module_id' => $module->id],
            ['completed_at' => now(), 'quiz_passed' => true],
        );

        $course = $module->course;
        $moduleIds = $course->modules()->pluck('id');

        $passedCount = $teacherProfile->moduleCompletions()
            ->whereIn('module_id', $moduleIds)
            ->where('quiz_passed', true)
            ->count();

        if ($passedCount === $moduleIds->count()) {
            $teacherProfile->courseCompletions()->updateOrCreate(
                ['course_id' => $course->id],
                ['completed_at' => now(), 'quiz_passed' => true],
            );
        }
    }
}
