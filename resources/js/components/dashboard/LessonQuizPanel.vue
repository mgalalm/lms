<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { LessonQuizResult, LessonQuizSummary } from '@/types';
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{
    lessonId: number;
    quiz: LessonQuizSummary;
    result: LessonQuizResult | null;
}>();

const answers = reactive<Record<number, string>>({});
const submitting = ref(false);

const allQuestionsAnswered = computed(
    () => props.quiz.questions.length > 0 && props.quiz.questions.every((question) => answers[question.id] !== undefined),
);

function resultFor(questionId: number) {
    return props.result?.results.find((result) => result.id === questionId) ?? null;
}

function submit(): void {
    router.post(
        route('lessons.quiz.submit', props.lessonId),
        { answers },
        {
            preserveScroll: true,
            onStart: () => (submitting.value = true),
            onFinish: () => (submitting.value = false),
        },
    );
}
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="result"
            class="rounded-lg border p-3 text-sm"
            :class="
                result.score >= 80
                    ? 'border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-300'
                    : 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300'
            "
        >
            <p class="font-medium">You scored {{ result.score }}% ({{ result.correct }}/{{ result.total }} correct)</p>
        </div>

        <div v-for="(question, index) in quiz.questions" :key="question.id" class="space-y-2">
            <p class="text-sm font-medium">{{ index + 1 }}. {{ question.question_text }}</p>
            <label v-for="option in question.options" :key="option" class="flex items-center gap-2 text-sm">
                <input
                    type="radio"
                    :name="`lesson-quiz-question-${question.id}`"
                    :value="option"
                    :checked="answers[question.id] === option"
                    :disabled="!!result"
                    @change="answers[question.id] = option"
                />
                {{ option }}
            </label>

            <p v-if="resultFor(question.id)" class="text-xs" :class="resultFor(question.id)?.is_correct ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'">
                {{ resultFor(question.id)?.is_correct ? 'Correct' : `Correct answer: ${resultFor(question.id)?.correct_answer}` }}
                <span v-if="resultFor(question.id)?.explanation" class="text-muted-foreground"> — {{ resultFor(question.id)?.explanation }} </span>
            </p>
        </div>

        <Button v-if="!result" size="sm" :disabled="!allQuestionsAnswered || submitting" @click="submit">Submit Quiz</Button>
    </div>
</template>
