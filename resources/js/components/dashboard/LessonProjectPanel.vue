<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { LessonProjectSummary, ProjectSubmission } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Star, UploadCloud, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    project: LessonProjectSummary;
    submissions: ProjectSubmission[];
}>();

const RATING_LABELS: Record<number, string> = {
    1: 'Needs Work',
    2: 'Fair',
    3: 'Good',
    4: 'Very Good',
    5: 'Excellent',
};

const latest = computed(() => props.submissions[0] ?? null);
const previousAttempts = computed(() => props.submissions.slice(1));
const showForm = computed(() => !latest.value || latest.value.status === 'reviewed');
const previousAttemptsExpanded = ref(false);

const form = useForm<{ title: string; description: string; live_url: string; screenshots: File[] }>({
    title: '',
    description: '',
    live_url: '',
    screenshots: [],
});

const previews = ref<string[]>([]);
const isDragging = ref(false);

function addFiles(fileList: FileList | null): void {
    if (!fileList) {
        return;
    }

    for (const file of Array.from(fileList)) {
        form.screenshots.push(file);
        previews.value.push(URL.createObjectURL(file));
    }
}

function onDrop(event: DragEvent): void {
    isDragging.value = false;
    addFiles(event.dataTransfer?.files ?? null);
}

function onFileInput(event: Event): void {
    addFiles((event.target as HTMLInputElement).files);
    (event.target as HTMLInputElement).value = '';
}

function removeScreenshot(index: number): void {
    form.screenshots.splice(index, 1);
    URL.revokeObjectURL(previews.value[index]);
    previews.value.splice(index, 1);
}

function submit(): void {
    form.post(route('lesson-projects.submissions.store', props.project.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            previews.value.forEach((url) => URL.revokeObjectURL(url));
            previews.value = [];
        },
    });
}
</script>

<template>
    <div class="space-y-4">
        <div v-if="project.brief" class="rounded-lg bg-muted p-3 text-sm text-muted-foreground">
            {{ project.brief }}
        </div>

        <div
            v-if="latest && latest.status === 'reviewed' && latest.feedback"
            class="space-y-2 rounded-lg border p-3"
            :class="
                latest.feedback.is_featured
                    ? 'border-amber-400 bg-amber-50 dark:bg-amber-950/30'
                    : 'border-sidebar-border/70 dark:border-sidebar-border'
            "
        >
            <div v-if="latest.feedback.is_featured" class="flex items-center gap-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
                <Star class="size-3.5 fill-amber-500 text-amber-500" />
                Featured Project
            </div>
            <p class="text-sm font-medium">{{ latest.title }}</p>
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-0.5">
                    <Star
                        v-for="value in 5"
                        :key="value"
                        class="size-4"
                        :class="value <= latest.feedback.rating ? 'fill-primary text-primary' : 'text-muted-foreground'"
                    />
                </div>
                <span class="text-xs text-muted-foreground">{{ RATING_LABELS[latest.feedback.rating] }}</span>
            </div>
            <p class="text-sm text-muted-foreground">{{ latest.feedback.comment }}</p>
        </div>

        <div
            v-else-if="latest && latest.status === 'submitted'"
            class="rounded-lg border border-sidebar-border/70 p-3 text-sm text-muted-foreground dark:border-sidebar-border"
        >
            <p class="font-medium text-foreground">{{ latest.title }}</p>
            <p>Submitted — waiting for review.</p>
        </div>

        <form v-if="showForm" class="space-y-3" @submit.prevent="submit">
            <div class="grid gap-1.5">
                <label class="text-xs font-medium text-muted-foreground" for="project-title">Project Title</label>
                <input
                    id="project-title"
                    v-model="form.title"
                    type="text"
                    required
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
                <p v-if="form.errors.title" class="text-xs text-destructive">{{ form.errors.title }}</p>
            </div>

            <div class="grid gap-1.5">
                <label class="text-xs font-medium text-muted-foreground" for="project-description">Description</label>
                <textarea
                    id="project-description"
                    v-model="form.description"
                    rows="3"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
            </div>

            <div class="grid gap-1.5">
                <label class="text-xs font-medium text-muted-foreground" for="project-live-url">Live URL (optional)</label>
                <input
                    id="project-live-url"
                    v-model="form.live_url"
                    type="url"
                    placeholder="https://"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
                <p v-if="form.errors.live_url" class="text-xs text-destructive">{{ form.errors.live_url }}</p>
            </div>

            <div
                class="flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-6 text-center transition-colors"
                :class="isDragging ? 'border-primary bg-primary/5' : 'border-sidebar-border/70 dark:border-sidebar-border'"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onDrop"
            >
                <UploadCloud class="size-6 text-muted-foreground" />
                <p class="text-xs text-muted-foreground">Drag & drop screenshots here, or</p>
                <label class="cursor-pointer text-xs font-medium text-primary hover:underline">
                    browse files
                    <input type="file" accept="image/*" multiple class="hidden" @change="onFileInput" />
                </label>
            </div>

            <div v-if="previews.length" class="flex flex-wrap gap-2">
                <div v-for="(preview, index) in previews" :key="preview" class="relative">
                    <img :src="preview" class="size-16 rounded-md border border-sidebar-border/70 object-cover dark:border-sidebar-border" alt="" />
                    <button
                        type="button"
                        class="absolute -right-1.5 -top-1.5 flex size-5 items-center justify-center rounded-full bg-destructive text-destructive-foreground"
                        @click="removeScreenshot(index)"
                    >
                        <X class="size-3" />
                    </button>
                </div>
            </div>

            <Button type="submit" size="sm" :disabled="form.processing">
                {{ latest ? 'Submit New Attempt' : 'Submit Project' }}
            </Button>
        </form>

        <div v-if="previousAttempts.length">
            <button
                type="button"
                class="text-xs font-medium text-primary hover:underline"
                @click="previousAttemptsExpanded = !previousAttemptsExpanded"
            >
                {{ previousAttemptsExpanded ? 'Hide' : 'Show' }} Previous Attempts ({{ previousAttempts.length }})
            </button>
            <ul v-if="previousAttemptsExpanded" class="mt-2 space-y-2">
                <li
                    v-for="attempt in previousAttempts"
                    :key="attempt.id"
                    class="flex items-center justify-between rounded-md border border-sidebar-border/70 p-2 text-xs dark:border-sidebar-border"
                >
                    <span>{{ attempt.title }} · {{ new Date(attempt.submitted_at).toLocaleDateString() }}</span>
                    <span v-if="attempt.feedback" class="flex items-center gap-0.5 font-medium">
                        {{ attempt.feedback.rating }}<Star class="size-3 fill-primary text-primary" />
                    </span>
                    <span v-else class="text-muted-foreground">Pending</span>
                </li>
            </ul>
        </div>
    </div>
</template>
