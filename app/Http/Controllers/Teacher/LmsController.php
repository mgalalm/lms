<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\CourseProgressStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LmsCourseCardResource;
use App\Http\Resources\LmsCourseDetailResource;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LmsController extends Controller
{
    /**
     * The teacher's course library, grouped by grade band, with their own
     * completion progress and certification status for each course.
     */
    public function index(Request $request): Response
    {
        $filter = $request->string('filter', 'all')->value();
        $teacherProfile = $request->user()->teacherProfile;

        $courses = Course::query()->withCount('lessons')->orderBy('sort_order')->get();

        $completedCounts = $teacherProfile->completedLessonCountsByCourse();
        $completions = $teacherProfile->courseCompletions()->get()->keyBy('course_id');

        $courses->each(function (Course $course) use ($completedCounts, $completions) {
            $completedLessons = (int) $completedCounts->get($course->id, 0);
            $completion = $completions->get($course->id);

            $course->completed_lessons_count = $completedLessons;
            $course->progress_status = CourseProgressStatus::resolve(
                $completedLessons,
                $course->lessons_count,
                $completion?->isCertified() ?? false,
            );
        });

        if ($filter !== 'all') {
            $courses = $courses->filter(fn (Course $course) => $course->progress_status->filterCategory() === $filter)->values();
        }

        $gradeBands = $courses->groupBy(fn (Course $course) => $course->grade_band ?? 'Ungraded')
            ->map(fn ($coursesInBand, $gradeBand) => [
                'grade_band' => $gradeBand,
                'courses' => LmsCourseCardResource::collection($coursesInBand),
            ])
            ->values();

        return Inertia::render('teacher/Lms/Index', [
            'gradeBands' => $gradeBands,
            'filter' => $filter,
        ]);
    }

    /**
     * A single course's lessons and certification quiz, with the teacher's
     * own progress marked against each lesson.
     */
    public function show(Request $request, Course $course): Response
    {
        $course->load(['modules.lessons.video', 'modules.lessons.slideshow', 'quiz.questions']);

        $teacherProfile = $request->user()->teacherProfile;
        $lessonIds = $course->modules->flatMap(fn ($module) => $module->lessons)->pluck('id');

        $completedLessonIds = $teacherProfile->lessonCompletions()
            ->whereIn('lesson_id', $lessonIds)
            ->pluck('lesson_id');

        $isCertified = $teacherProfile->isCertifiedForCourse($course);
        $canTakeQuiz = ! $isCertified
            && $course->quiz !== null
            && $teacherProfile->hasCompletedAllLessonsForCourse($course);

        return Inertia::render('teacher/Lms/Show', [
            'course' => new LmsCourseDetailResource($course, $completedLessonIds, $isCertified, $canTakeQuiz),
        ]);
    }
}
