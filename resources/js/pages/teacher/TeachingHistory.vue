<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import ProjectCard from '@/components/teacher/ProjectCard.vue';
import ProjectDetailDialog from '@/components/teacher/ProjectDetailDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, ClassSession, FeedbackTemplate, Paginated, ProjectSubmission } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CalendarClock, FolderCheck } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    pastSessions: Paginated<ClassSession>;
    reviewedProjects: Paginated<ProjectSubmission>;
    feedbackTemplates: FeedbackTemplate[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Teaching', href: route('teaching.index') },
    { title: 'History', href: route('teaching.history') },
];

function lessonPath(session: ClassSession): string {
    return [session.lesson.module?.course?.title, session.lesson.module?.title, session.lesson.title].filter(Boolean).join(' / ');
}

function scheduledLabel(session: ClassSession): string {
    return new Date(session.scheduled_at).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
}

const openSubmissionId = ref<number | null>(null);
</script>

<template>
    <Head title="Teaching History" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4">
            <section class="space-y-3">
                <h2 class="text-sm font-semibold text-muted-foreground">Past Sessions</h2>

                <EmptyState v-if="!pastSessions.data.length" :icon="CalendarClock" heading="No past sessions yet" description="" />

                <div
                    v-else
                    class="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70 bg-card dark:divide-sidebar-border dark:border-sidebar-border"
                >
                    <div v-for="session in pastSessions.data" :key="session.id" class="flex items-center justify-between gap-4 p-4">
                        <div>
                            <div class="font-medium">{{ lessonPath(session) }}</div>
                            <div class="text-sm text-muted-foreground">
                                {{ session.student?.name }}
                                <span v-if="session.student?.code">({{ session.student.code }})</span>
                                &middot; {{ scheduledLabel(session) }}
                            </div>
                        </div>
                    </div>
                </div>

                <Pagination :links="pastSessions.meta.links" />
            </section>

            <section class="space-y-3">
                <h2 class="text-sm font-semibold text-muted-foreground">Reviewed Projects</h2>

                <EmptyState v-if="!reviewedProjects.data.length" :icon="FolderCheck" heading="No reviewed projects yet" description="" />

                <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <ProjectCard
                        v-for="submission in reviewedProjects.data"
                        :key="submission.id"
                        :submission="submission"
                        @open="openSubmissionId = submission.id"
                    />
                </div>

                <Pagination :links="reviewedProjects.meta.links" />
            </section>
        </div>

        <ProjectDetailDialog
            :submission-id="openSubmissionId"
            :templates="feedbackTemplates"
            @close="openSubmissionId = null"
            @reviewed="router.reload({ only: ['reviewedProjects'] })"
        />
    </AppLayout>
</template>
