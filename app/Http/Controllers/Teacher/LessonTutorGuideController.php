<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonTutorGuide;
use App\Support\Markdown\TutorGuideMarkdownRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LessonTutorGuideController extends Controller
{
    /**
     * Teacher/admin-only lesson prep material. Enforced here via the
     * policy — not just hidden in the UI — so a student hitting this URL
     * directly is blocked, not just unable to find a link to it.
     */
    public function __invoke(Request $request, Lesson $lesson, TutorGuideMarkdownRenderer $markdown): Response
    {
        $guide = $lesson->tutorGuide;

        Gate::authorize('view', $guide ?? LessonTutorGuide::make(['lesson_id' => $lesson->id]));

        $introMarkdown = $guide?->introMarkdown();

        return Inertia::render('teacher/Lms/TutorGuide', [
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
            ],
            'introHtml' => $markdown->render($introMarkdown),
        ]);
    }
}
