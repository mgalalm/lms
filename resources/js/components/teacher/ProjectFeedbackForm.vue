<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type { FeedbackSentiment, FeedbackTemplate, ProjectSubmission } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { onClickOutside } from '@vueuse/core';
import { ChevronDown, Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    submission: ProjectSubmission;
    templates: FeedbackTemplate[];
}>();

const emit = defineEmits<{ saved: [] }>();

const RATING_LABELS: Record<number, string> = {
    1: 'Needs Work',
    2: 'Fair',
    3: 'Good',
    4: 'Very Good',
    5: 'Excellent',
};

const SENTIMENT_LABELS: Record<FeedbackSentiment, string> = {
    positive: 'Positive',
    neutral: 'Neutral',
    needs_improvement: 'Needs Improvement',
};

const form = useForm({
    rating: props.submission.feedback?.rating ?? 0,
    comment: props.submission.feedback?.comment ?? '',
    is_featured: props.submission.feedback?.is_featured ?? false,
});

const hoveredRating = ref(0);
const displayedRating = computed(() => hoveredRating.value || form.rating);

const templatesBySentiment = computed(() => {
    const groups = {} as Record<FeedbackSentiment, FeedbackTemplate[]>;

    for (const template of props.templates) {
        (groups[template.sentiment] ??= []).push(template);
    }

    return groups;
});

function applyTemplate(body: string): void {
    form.comment = form.comment ? `${form.comment}\n\n${body}` : body;
    templateMenuOpen.value = false;
}

// A plain toggled panel rather than the Radix DropdownMenu component —
// nested inside this form's parent Dialog (also a Radix portal/focus-trap
// primitive), the dropdown's own portal never actually opened.
const templateMenuOpen = ref(false);
const templateMenuRef = ref<HTMLElement | null>(null);
onClickOutside(templateMenuRef, () => (templateMenuOpen.value = false));

function submit(): void {
    form.post(route('teaching.projects.feedback', props.submission.id), {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    });
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div>
            <p class="mb-1 text-xs font-medium text-muted-foreground">Rating</p>
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1" @mouseleave="hoveredRating = 0">
                    <button
                        v-for="value in 5"
                        :key="value"
                        type="button"
                        class="rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        :aria-label="`Rate ${value} out of 5`"
                        @mouseenter="hoveredRating = value"
                        @focus="hoveredRating = value"
                        @click="form.rating = value"
                    >
                        <Star class="size-6" :class="value <= displayedRating ? 'fill-primary text-primary' : 'text-muted-foreground'" />
                    </button>
                </div>
                <span v-if="displayedRating" class="text-sm text-muted-foreground">{{ RATING_LABELS[displayedRating] }}</span>
            </div>
            <p v-if="form.errors.rating" class="mt-1 text-xs text-destructive">{{ form.errors.rating }}</p>
        </div>

        <Label class="flex items-center gap-2 text-sm font-normal">
            <Checkbox v-model:checked="form.is_featured" />
            Mark as Featured Project
        </Label>

        <div>
            <div class="mb-1 flex items-center justify-between">
                <label class="text-xs font-medium text-muted-foreground" for="feedback-comment">Comment</label>
                <div ref="templateMenuRef" class="relative">
                    <Button type="button" variant="ghost" size="sm" class="h-6 text-xs" @click="templateMenuOpen = !templateMenuOpen">
                        Use a template
                        <ChevronDown class="ml-1 size-3" />
                    </Button>
                    <div
                        v-if="templateMenuOpen"
                        class="absolute right-0 z-50 mt-1 max-h-80 w-72 overflow-y-auto rounded-md border border-sidebar-border/70 bg-popover p-1 shadow-md dark:border-sidebar-border"
                    >
                        <template v-for="(group, sentiment) in templatesBySentiment" :key="sentiment">
                            <p class="px-2 py-1.5 text-xs font-medium text-muted-foreground">{{ SENTIMENT_LABELS[sentiment] }}</p>
                            <button
                                v-for="template in group"
                                :key="template.id"
                                type="button"
                                class="block w-full rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent"
                                @click="applyTemplate(template.body)"
                            >
                                {{ template.label }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <textarea
                id="feedback-comment"
                v-model="form.comment"
                rows="5"
                required
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            />
            <p v-if="form.errors.comment" class="mt-1 text-xs text-destructive">{{ form.errors.comment }}</p>
        </div>

        <Button type="submit" :disabled="form.processing || !form.rating">Save Feedback</Button>
    </form>
</template>
