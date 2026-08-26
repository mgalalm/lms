<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import type { FeedbackSentiment, FeedbackTemplate, ProjectSubmission } from '@/types';
import { useForm } from '@inertiajs/vue3';
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
}

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
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button type="button" variant="ghost" size="sm" class="h-6 text-xs">
                            Use a template
                            <ChevronDown class="ml-1 size-3" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-72">
                        <template v-for="(group, sentiment) in templatesBySentiment" :key="sentiment">
                            <DropdownMenuLabel class="text-xs text-muted-foreground">{{ SENTIMENT_LABELS[sentiment] }}</DropdownMenuLabel>
                            <DropdownMenuItem v-for="template in group" :key="template.id" @click="applyTemplate(template.body)">
                                {{ template.label }}
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                        </template>
                    </DropdownMenuContent>
                </DropdownMenu>
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
