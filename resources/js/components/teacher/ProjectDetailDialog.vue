<script setup lang="ts">
import ProjectFeedbackForm from '@/components/teacher/ProjectFeedbackForm.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { FeedbackTemplate, ProjectSubmission } from '@/types';
import { ExternalLink, Loader2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    submissionId: number | null;
    templates: FeedbackTemplate[];
}>();

const emit = defineEmits<{ close: []; reviewed: [] }>();

const submission = ref<ProjectSubmission | null>(null);
const loading = ref(false);
const activeImageIndex = ref(0);

const open = computed({
    get: () => props.submissionId !== null,
    set: (value: boolean) => {
        if (!value) {
            emit('close');
        }
    },
});

watch(
    () => props.submissionId,
    async (id) => {
        submission.value = null;
        activeImageIndex.value = 0;

        if (id === null) {
            return;
        }

        loading.value = true;

        try {
            const response = await fetch(route('teaching.projects.show', id));
            submission.value = await response.json();
        } finally {
            loading.value = false;
        }
    },
    { immediate: true },
);

function showPrevious(): void {
    if (!submission.value?.screenshots.length) {
        return;
    }

    activeImageIndex.value = (activeImageIndex.value - 1 + submission.value.screenshots.length) % submission.value.screenshots.length;
}

function showNext(): void {
    if (!submission.value?.screenshots.length) {
        return;
    }

    activeImageIndex.value = (activeImageIndex.value + 1) % submission.value.screenshots.length;
}

function onKeydown(event: KeyboardEvent): void {
    // Don't hijack the browser's normal cursor movement while the teacher
    // is typing feedback — only steer the gallery when focus is elsewhere.
    const target = event.target as HTMLElement;

    if (target.tagName === 'TEXTAREA' || target.tagName === 'INPUT') {
        return;
    }

    if (event.key === 'ArrowLeft') {
        showPrevious();
    } else if (event.key === 'ArrowRight') {
        showNext();
    }
}

function onReviewed(): void {
    emit('reviewed');
    emit('close');
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-4xl" @keydown="onKeydown">
            <DialogHeader>
                <DialogTitle>{{ submission?.title ?? 'Loading…' }}</DialogTitle>
            </DialogHeader>

            <div v-if="loading" class="flex items-center justify-center py-12">
                <Loader2 class="size-6 animate-spin text-muted-foreground" />
            </div>

            <div v-else-if="submission" class="grid gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <div class="flex aspect-video items-center justify-center overflow-hidden rounded-lg bg-muted">
                        <img
                            v-if="submission.screenshots.length"
                            :src="submission.screenshots[activeImageIndex].url"
                            class="size-full object-contain"
                            alt=""
                        />
                        <p v-else class="text-sm text-muted-foreground">No screenshots</p>
                    </div>

                    <div v-if="submission.screenshots.length > 1" class="flex gap-2 overflow-x-auto">
                        <button
                            v-for="(screenshot, index) in submission.screenshots"
                            :key="screenshot.id"
                            type="button"
                            class="size-14 shrink-0 overflow-hidden rounded-md border-2"
                            :class="index === activeImageIndex ? 'border-primary' : 'border-transparent'"
                            @click="activeImageIndex = index"
                        >
                            <img :src="screenshot.url" class="size-full object-cover" alt="" />
                        </button>
                    </div>

                    <p v-if="submission.description" class="whitespace-pre-line text-sm text-muted-foreground">{{ submission.description }}</p>

                    <a
                        v-if="submission.live_url"
                        :href="submission.live_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 text-sm text-primary hover:underline"
                    >
                        View Live Project
                        <ExternalLink class="size-3.5" />
                    </a>
                </div>

                <ProjectFeedbackForm :submission="submission" :templates="templates" @saved="onReviewed" />
            </div>
        </DialogContent>
    </Dialog>
</template>
