<script setup lang="ts">
import type { ProjectSubmission } from '@/types';
import { ImageOff, Star } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    submission: ProjectSubmission;
}>();

const emit = defineEmits<{ open: [] }>();

const lessonPath = computed(() => {
    const lesson = props.submission.lesson_project?.lesson;

    return [lesson?.module?.course?.grade_band, lesson?.module?.title, lesson?.title].filter(Boolean).join(' / ');
});

const submittedLabel = computed(() =>
    new Date(props.submission.submitted_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }),
);

const thumbnail = computed(() => props.submission.screenshots[0]?.url ?? null);
const isFeatured = computed(() => props.submission.feedback?.is_featured ?? false);
</script>

<template>
    <button
        type="button"
        class="flex flex-col overflow-hidden rounded-xl border bg-card text-left transition-shadow hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
        :class="isFeatured ? 'border-amber-400' : 'border-sidebar-border/70 dark:border-sidebar-border'"
        @click="emit('open')"
    >
        <div class="relative flex aspect-video items-center justify-center bg-muted">
            <img v-if="thumbnail" :src="thumbnail" class="size-full object-cover" alt="" />
            <ImageOff v-else class="size-8 text-muted-foreground" />
            <div
                v-if="isFeatured"
                class="absolute right-2 top-2 flex items-center gap-1 rounded-full bg-amber-400 px-2 py-0.5 text-xs font-semibold text-amber-950"
            >
                <Star class="size-3 fill-amber-950" />
                Featured
            </div>
        </div>

        <div class="flex flex-1 flex-col gap-1 p-3">
            <p class="line-clamp-1 text-sm font-medium">{{ submission.title }}</p>
            <p class="line-clamp-1 text-xs text-muted-foreground">{{ lessonPath }}</p>
            <div class="mt-1 flex items-center justify-between text-xs text-muted-foreground">
                <span>
                    {{ submission.student?.name }}
                    <span v-if="submission.student?.code">({{ submission.student.code }})</span>
                </span>
                <span>{{ submittedLabel }}</span>
            </div>
        </div>
    </button>
</template>
