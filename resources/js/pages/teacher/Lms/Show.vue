<script setup lang="ts">
import LessonSlideshowViewer from '@/components/LessonSlideshowViewer.vue';
import LessonVideoPlayer from '@/components/LessonVideoPlayer.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, LmsCourseDetail, LmsModule, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, reactive, ref } from 'vue';

const props = defineProps<{
    course: LmsCourseDetail;
}>();

const expandedVideoLessonIds = ref<Set<number>>(new Set());
const expandedSlideshowLessonIds = ref<Set<number>>(new Set());

function toggleExpanded(set: Set<number>, lessonId: number): Set<number> {
    const next = new Set(set);

    if (next.has(lessonId)) {
        next.delete(lessonId);
    } else {
        next.add(lessonId);
    }

    return next;
}

function toggleVideo(lessonId: number): void {
    expandedVideoLessonIds.value = toggleExpanded(expandedVideoLessonIds.value, lessonId);
}

function toggleSlideshow(lessonId: number): void {
    const opening = !expandedSlideshowLessonIds.value.has(lessonId);

    expandedSlideshowLessonIds.value = toggleExpanded(expandedSlideshowLessonIds.value, lessonId);

    if (opening) {
        // Resolves the lesson's session_prep task(s) — see
        // LessonSlideshowViewedController. Fire-and-forget: the embed
        // itself is a plain client-side iframe with no backend hit of
        // its own, so this is the only signal the teacher opened it.
        router.post(route('lessons.slideshow.viewed', lessonId), {}, { preserveScroll: true, preserveState: true });
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'LMS', href: route('lms.index') },
    { title: props.course.grade_band, href: route('lms.show', props.course.id) },
];

const page = usePage<SharedData>();
const moduleQuizResult = computed(() => page.props.flash.moduleQuizResult);

const moduleAnswers = reactive<Record<number, Record<number, string>>>({});

function answersFor(moduleId: number): Record<number, string> {
    if (!moduleAnswers[moduleId]) {
        moduleAnswers[moduleId] = {};
    }

    return moduleAnswers[moduleId];
}

function moduleQuizAnswered(module: LmsModule): boolean {
    const questions = module.quiz.questions;

    return questions.length > 0 && questions.every((question) => answersFor(module.id)[question.id] !== undefined);
}

function startModuleQuiz(module: LmsModule): void {
    router.post(route('lms.modules.quiz.start', [props.course.id, module.id]), {}, { preserveScroll: true });
}

function submitModuleQuiz(module: LmsModule): void {
    router.post(
        route('lms.modules.quiz.submit', [props.course.id, module.id]),
        { answers: answersFor(module.id) },
        {
            preserveScroll: true,
            onSuccess: () => {
                moduleAnswers[module.id] = {};
            },
        },
    );
}

function markLessonComplete(lessonId: number): void {
    router.post(route('lms.lessons.complete', [props.course.id, lessonId]), {}, { preserveScroll: true });
}

/**
 * Deep-linked here from a session's "Lesson" menu (?lesson=X&open=video, or
 * ?open=quiz) — scroll to and auto-expand the requested content. Done via
 * query params + JS on mount rather than a plain #hash, since Inertia's
 * own scroll-reset on navigation isn't guaranteed to play nicely with the
 * browser's native hash-scroll timing.
 */
onMounted(async () => {
    const params = new URLSearchParams(window.location.search);
    const lessonId = Number(params.get('lesson'));
    const open = params.get('open');

    if (open === 'quiz') {
        await nextTick();
        document.getElementById('course-quiz')?.scrollIntoView({ behavior: 'smooth', block: 'start' });

        return;
    }

    if (!lessonId) {
        return;
    }

    if (open === 'video') {
        toggleVideo(lessonId);
    } else if (open === 'slideshow') {
        toggleSlideshow(lessonId);
    }

    await nextTick();
    document.getElementById(`lesson-${lessonId}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
});
</script>

<template>
    <Head :title="course.grade_band" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div>
                <div class="flex items-center justify-between gap-2">
                    <h1 class="text-lg font-semibold">{{ course.grade_band }}</h1>
                    <span
                        v-if="course.is_certified"
                        class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-300"
                    >
                        Certified
                    </span>
                </div>
                <p v-if="course.grade_band" class="text-sm text-muted-foreground">{{ course.grade_band }}</p>

                <div class="mt-3 space-y-1">
                    <div class="h-2 max-w-sm overflow-hidden rounded-full bg-muted">
                        <div class="h-full rounded-full bg-primary" :style="{ width: `${course.progress_percentage}%` }" />
                    </div>
                    <p class="text-xs text-muted-foreground">{{ course.progress_percentage }}% Completed</p>
                </div>
            </div>

            <div v-for="module in course.modules" :key="module.id" class="space-y-3">
                <h2 class="text-sm font-semibold text-muted-foreground">{{ module.title }}</h2>

                <div
                    class="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70 bg-card dark:divide-sidebar-border dark:border-sidebar-border"
                >
                    <div v-for="lesson in module.lessons" :id="`lesson-${lesson.id}`" :key="lesson.id" class="scroll-mt-4 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-medium">{{ lesson.title }}</p>
                                <p v-if="lesson.reading_content" class="mt-1 whitespace-pre-line text-sm text-muted-foreground">
                                    {{ lesson.reading_content }}
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <Button v-if="lesson.has_video" size="sm" variant="outline" @click="toggleVideo(lesson.id)">
                                    {{ expandedVideoLessonIds.has(lesson.id) ? 'Hide Video' : 'Watch Video' }}
                                </Button>
                                <Button v-if="lesson.slideshow" size="sm" variant="outline" @click="toggleSlideshow(lesson.id)">
                                    {{ expandedSlideshowLessonIds.has(lesson.id) ? 'Hide Slideshow' : 'View Slideshow' }}
                                </Button>
                                <Button as-child size="sm" variant="outline">
                                    <Link :href="route('lessons.tutor-guide', lesson.id)">Tutor Guide</Link>
                                </Button>
                                <Button v-if="lesson.completed" disabled size="sm" variant="secondary">Completed</Button>
                                <Button v-else size="sm" @click="markLessonComplete(lesson.id)">Mark as complete</Button>
                            </div>
                        </div>

                        <LessonVideoPlayer v-if="expandedVideoLessonIds.has(lesson.id)" :lesson-id="lesson.id" class="mt-3" />
                        <LessonSlideshowViewer
                            v-if="expandedSlideshowLessonIds.has(lesson.id) && lesson.slideshow"
                            :google-slides-id="lesson.slideshow.google_slides_id"
                            class="mt-3"
                        />
                    </div>
                </div>

                <div
                    :id="`module-quiz-${module.id}`"
                    class="scroll-mt-4 space-y-3 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold">Module Quiz</h3>
                        <span
                            v-if="module.quiz.certified"
                            class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-950 dark:text-green-300"
                        >
                            Certified
                        </span>
                    </div>

                    <div
                        v-if="moduleQuizResult && moduleQuizResult.module_id === module.id"
                        class="rounded-lg border p-3 text-sm"
                        :class="
                            moduleQuizResult.passed
                                ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-300'
                                : 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-300'
                        "
                    >
                        <p v-if="moduleQuizResult.passed" class="font-medium">
                            Passed with {{ moduleQuizResult.score }}% ({{ moduleQuizResult.correct }}/{{ moduleQuizResult.total }}). You're now
                            certified to teach this module.
                        </p>
                        <p v-else class="font-medium">
                            Scored {{ moduleQuizResult.score }}% ({{ moduleQuizResult.correct }}/{{ moduleQuizResult.total }}) —
                            {{ moduleQuizResult.attempts_remaining }} attempt(s) left.
                        </p>
                    </div>

                    <p v-if="module.quiz.certified" class="text-sm text-muted-foreground">
                        You've passed this module's quiz — no need to retake it.
                    </p>
                    <p v-else-if="!module.quiz.is_ready" class="text-sm text-muted-foreground">
                        This module has no quiz questions yet. Ask an admin to generate and publish quizzes for its lessons.
                    </p>
                    <p v-else-if="!module.quiz.lessons_complete" class="text-sm text-muted-foreground">
                        Complete every lesson above to unlock this module's quiz.
                    </p>
                    <p
                        v-else-if="module.quiz.attempts_remaining === 0 && !module.quiz.has_open_attempt"
                        class="text-sm text-destructive"
                    >
                        You've used all your attempts without passing. Ask an admin to reset your attempts before trying again.
                    </p>

                    <template v-else-if="module.quiz.can_attempt || module.quiz.has_open_attempt">
                        <p class="text-sm text-muted-foreground">
                            {{ module.quiz.passing_score }}% to pass · {{ module.quiz.attempts_remaining }} attempt(s) left ·
                            questions are drawn fresh each attempt.
                        </p>

                        <Button v-if="!module.quiz.questions.length" size="sm" @click="startModuleQuiz(module)">Start Quiz</Button>

                        <div v-else class="space-y-4">
                            <div v-for="(question, index) in module.quiz.questions" :key="question.id" class="space-y-2">
                                <p class="text-sm font-medium">{{ index + 1 }}. {{ question.question_text }}</p>
                                <label v-for="option in question.options" :key="option" class="flex items-center gap-2 text-sm">
                                    <input
                                        type="radio"
                                        :name="`module-${module.id}-question-${question.id}`"
                                        :value="option"
                                        :checked="answersFor(module.id)[question.id] === option"
                                        @change="answersFor(module.id)[question.id] = option"
                                    />
                                    {{ option }}
                                </label>
                            </div>

                            <Button size="sm" :disabled="!moduleQuizAnswered(module)" @click="submitModuleQuiz(module)">Submit Quiz</Button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
