<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import ProjectCard from '@/components/teacher/ProjectCard.vue';
import ProjectDetailDialog from '@/components/teacher/ProjectDetailDialog.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, FeedbackTemplate, Paginated, ProjectSubmission } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { FolderCheck } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    submissions: Paginated<ProjectSubmission>;
    status: 'submitted' | 'reviewed';
    counts: { pending: number; reviewed: number };
    filters: { from: string; to: string; search: string; featured: boolean };
    feedbackTemplates: FeedbackTemplate[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Projects', href: route('teaching.projects.index') }];

const from = ref(props.filters.from);
const to = ref(props.filters.to);
const search = ref(props.filters.search);
const featuredOnly = ref(props.filters.featured);
const openSubmissionId = ref<number | null>(null);

const tabs: { key: 'submitted' | 'reviewed'; label: string; count: number }[] = [
    { key: 'submitted', label: 'Pending Review', count: props.counts.pending },
    { key: 'reviewed', label: 'Reviewed', count: props.counts.reviewed },
];

function applyFilters(status: 'submitted' | 'reviewed' = props.status): void {
    router.get(
        route('teaching.projects.index'),
        {
            status,
            from: from.value,
            to: to.value,
            search: search.value,
            featured: featuredOnly.value ? 1 : undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}

// The native search input's clear (X) button — and just backspacing to
// empty — doesn't fire keyup.enter, so the stale filtered list would
// otherwise stick around until the user explicitly searches again.
watch(search, (value, previous) => {
    if (value === '' && previous !== '') {
        applyFilters();
    }
});

watch(featuredOnly, () => applyFilters());

function onReviewed(): void {
    router.reload({ only: ['submissions', 'counts'] });
}
</script>

<template>
    <Head title="Projects" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-center gap-2">
                <Button
                    v-for="tab in tabs"
                    :key="tab.key"
                    :variant="status === tab.key ? 'default' : 'outline'"
                    size="sm"
                    @click="applyFilters(tab.key)"
                >
                    {{ tab.label }}
                    <span class="ml-1.5 rounded-full px-1.5 py-0.5 text-xs" :class="status === tab.key ? 'bg-primary-foreground/20' : 'bg-muted'">
                        {{ tab.count }}
                    </span>
                </Button>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <label class="flex flex-col text-xs text-muted-foreground">
                    From
                    <input
                        v-model="from"
                        type="date"
                        class="rounded-md border border-input bg-background px-2 py-1 text-sm"
                        @change="applyFilters()"
                    />
                </label>
                <label class="flex flex-col text-xs text-muted-foreground">
                    To
                    <input
                        v-model="to"
                        type="date"
                        class="rounded-md border border-input bg-background px-2 py-1 text-sm"
                        @change="applyFilters()"
                    />
                </label>
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search by student name/ID or project title"
                    class="min-w-64 rounded-md border border-input bg-background px-3 py-1.5 text-sm"
                    @keyup.enter="applyFilters()"
                />
                <Label class="flex items-center gap-2 text-sm font-normal">
                    <Checkbox v-model:checked="featuredOnly" />
                    Featured only
                </Label>
            </div>

            <EmptyState
                v-if="!submissions.data.length"
                :icon="FolderCheck"
                :heading="status === 'submitted' ? 'No pending submissions' : 'No reviewed submissions yet'"
                :description="
                    status === 'submitted'
                        ? 'New project submissions from students assigned to you will show up here.'
                        : 'Once you review a submission, it will appear here.'
                "
            />

            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <ProjectCard
                    v-for="submission in submissions.data"
                    :key="submission.id"
                    :submission="submission"
                    @open="openSubmissionId = submission.id"
                />
            </div>

            <Pagination :links="submissions.meta.links" />
        </div>

        <ProjectDetailDialog
            :submission-id="openSubmissionId"
            :templates="feedbackTemplates"
            @close="openSubmissionId = null"
            @reviewed="onReviewed"
        />
    </AppLayout>
</template>
