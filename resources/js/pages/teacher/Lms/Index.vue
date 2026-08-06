<script setup lang="ts">
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, LmsFilter, LmsGradeBand } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{
    gradeBands: LmsGradeBand[];
    filter: LmsFilter;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'LMS', href: route('lms.index') }];

const tabs: { key: LmsFilter; label: string }[] = [
    { key: 'all', label: 'All' },
    { key: 'not_started', label: 'Not Started' },
    { key: 'in_progress', label: 'In Progress' },
    { key: 'completed', label: 'Completed' },
];

const statusPillClasses: Record<string, string> = {
    not_started: 'bg-muted text-muted-foreground',
    in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    quiz_pending: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    completed: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
};

const hasCourses = props.gradeBands.some((band) => band.courses.length > 0);
</script>

<template>
    <Head title="LMS" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex gap-2">
                <Link v-for="tab in tabs" :key="tab.key" :href="route('lms.index', { filter: tab.key })" preserve-state>
                    <Button :variant="filter === tab.key ? 'default' : 'outline'" size="sm">{{ tab.label }}</Button>
                </Link>
            </div>

            <p v-if="!hasCourses" class="text-sm text-muted-foreground">No courses match this filter.</p>

            <div v-for="band in gradeBands" :key="band.grade_band" class="space-y-3">
                <h2 v-if="band.courses.length > 0" class="text-sm font-semibold text-muted-foreground">{{ band.grade_band }}</h2>

                <div v-if="band.courses.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="course in band.courses"
                        :key="course.id"
                        :href="route('lms.show', course.id)"
                        class="flex flex-col overflow-hidden rounded-xl border border-sidebar-border/70 bg-card transition-colors hover:border-sidebar-border dark:border-sidebar-border"
                    >
                        <div class="flex h-32 items-center justify-center bg-muted">
                            <img v-if="course.thumbnail_url" :src="course.thumbnail_url" :alt="course.title" class="h-full w-full object-cover" />
                            <span v-else class="text-xs text-muted-foreground">No thumbnail</span>
                        </div>

                        <div class="flex flex-1 flex-col gap-2 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-medium">{{ course.title }}</h3>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" :class="statusPillClasses[course.status]">
                                    {{ course.status_label }}
                                </span>
                            </div>

                            <p class="text-xs text-muted-foreground">{{ course.lessons_count }} Lessons</p>

                            <div class="mt-auto space-y-1">
                                <div class="h-2 overflow-hidden rounded-full bg-muted">
                                    <div class="h-full rounded-full bg-primary" :style="{ width: `${course.progress_percentage}%` }" />
                                </div>
                                <p class="text-xs text-muted-foreground">{{ course.progress_percentage }}% Completed</p>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
