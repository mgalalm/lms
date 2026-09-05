<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import ProjectCard from '@/components/teacher/ProjectCard.vue';
import ProjectDetailDialog from '@/components/teacher/ProjectDetailDialog.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, ClassSession, FeedbackTemplate, Paginated, ProjectSubmission, SupportTicket, TeacherTask } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarClock, CheckCircle2, FolderCheck, LifeBuoy } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

const props = defineProps<{
    pastSessions: Paginated<ClassSession>;
    reviewedProjects: Paginated<ProjectSubmission>;
    completedTasks: Paginated<TeacherTask>;
    resolvedTickets: Paginated<SupportTicket>;
    feedbackTemplates: FeedbackTemplate[];
    filters: { search: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Teaching', href: route('teaching.index') },
    { title: 'Archived', href: route('teaching.archive') },
];

// All sections' data already arrive together (each independently
// paginated via its own query-string page name), so switching tabs is
// a pure client-side toggle — no extra round trip needed. The choice
// still lands in the URL (shallow, no navigation) so a tab is
// shareable/refreshable, matching the pattern already used for the
// Teaching feed's type chips.
type ArchiveTab = 'sessions' | 'projects' | 'tasks' | 'tickets';

const TABS: { key: ArchiveTab; label: string; count: number }[] = [
    { key: 'sessions', label: 'Sessions', count: props.pastSessions.meta.total },
    { key: 'projects', label: 'Projects', count: props.reviewedProjects.meta.total },
    { key: 'tasks', label: 'Tasks', count: props.completedTasks.meta.total },
    { key: 'tickets', label: 'Tickets', count: props.resolvedTickets.meta.total },
];

const activeTab = ref<ArchiveTab>('sessions');

onMounted(() => {
    const tabParam = new URLSearchParams(window.location.search).get('tab');

    if (tabParam === 'sessions' || tabParam === 'projects' || tabParam === 'tasks' || tabParam === 'tickets') {
        activeTab.value = tabParam;
    }
});

function selectTab(tab: ArchiveTab): void {
    activeTab.value = tab;

    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState(window.history.state, '', url);
}

// Search hits the server (each list is decrypted/filtered in PHP — see
// TeachingController::archive()), same debounce-on-clear idiom as the
// /teaching feed's search. `tab` rides along so the active tab survives
// the resulting page visit/remount.
const search = ref(props.filters.search);

function applySearch(): void {
    router.get(
        route('teaching.archive'),
        { search: search.value, tab: activeTab.value },
        { preserveState: true, preserveScroll: true },
    );
}

watch(search, (value, previous) => {
    if (value === '' && previous !== '') {
        applySearch();
    }
});

function lessonPath(session: ClassSession): string {
    return [session.lesson.module?.course?.grade_band, session.lesson.module?.title, session.lesson.title].filter(Boolean).join(' / ');
}

function scheduledLabel(session: ClassSession): string {
    return new Date(session.scheduled_at).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
}

function completedLabel(task: TeacherTask): string {
    return task.completed_at
        ? new Date(task.completed_at).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })
        : '';
}

function resolvedLabel(ticket: SupportTicket): string {
    return ticket.resolved_at
        ? new Date(ticket.resolved_at).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })
        : '';
}

const openSubmissionId = ref<number | null>(null);
</script>

<template>
    <Head title="Archived" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 class="text-xl font-semibold">Archived</h1>

            <input
                v-model="search"
                type="search"
                placeholder="Search by student name/ID, lesson, or title"
                class="min-w-64 rounded-md border border-input bg-background px-3 py-1.5 text-sm"
                @keyup.enter="applySearch()"
            />

            <div class="flex flex-wrap items-center gap-2">
                <Button
                    v-for="tab in TABS"
                    :key="tab.key"
                    :variant="activeTab === tab.key ? 'default' : 'outline'"
                    size="sm"
                    @click="selectTab(tab.key)"
                >
                    {{ tab.label }} ({{ tab.count }})
                </Button>
            </div>

            <section v-if="activeTab === 'sessions'" class="space-y-3">
                <EmptyState
                    v-if="!pastSessions.data.length"
                    :icon="CalendarClock"
                    :heading="search ? 'No past sessions match your search' : 'No past sessions yet'"
                    description=""
                />

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

            <section v-else-if="activeTab === 'projects'" class="space-y-3">
                <EmptyState
                    v-if="!reviewedProjects.data.length"
                    :icon="FolderCheck"
                    :heading="search ? 'No reviewed projects match your search' : 'No reviewed projects yet'"
                    description=""
                />

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

            <section v-else-if="activeTab === 'tasks'" class="space-y-3">
                <EmptyState
                    v-if="!completedTasks.data.length"
                    :icon="CheckCircle2"
                    :heading="search ? 'No completed tasks match your search' : 'No completed tasks yet'"
                    description=""
                />

                <div
                    v-else
                    class="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70 bg-card dark:divide-sidebar-border dark:border-sidebar-border"
                >
                    <div v-for="task in completedTasks.data" :key="task.id" class="flex items-center justify-between gap-4 p-4">
                        <div>
                            <div class="font-medium">{{ task.title }}</div>
                            <div v-if="task.description" class="text-sm text-muted-foreground">{{ task.description }}</div>
                        </div>
                        <span class="shrink-0 text-sm text-muted-foreground">Completed {{ completedLabel(task) }}</span>
                    </div>
                </div>

                <Pagination :links="completedTasks.meta.links" />
            </section>

            <section v-else class="space-y-3">
                <EmptyState
                    v-if="!resolvedTickets.data.length"
                    :icon="LifeBuoy"
                    :heading="search ? 'No resolved tickets match your search' : 'No resolved tickets yet'"
                    description=""
                />

                <div
                    v-else
                    class="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70 bg-card dark:divide-sidebar-border dark:border-sidebar-border"
                >
                    <Link
                        v-for="ticket in resolvedTickets.data"
                        :key="ticket.id"
                        :href="route('teaching.support-tickets.show', ticket.id)"
                        class="flex items-center justify-between gap-4 p-4 hover:bg-accent"
                    >
                        <div>
                            <div class="font-medium">{{ ticket.subject }}</div>
                            <div class="text-sm text-muted-foreground">
                                {{ ticket.code }}
                                <span v-if="ticket.class_session?.lesson_title">&middot; {{ ticket.class_session.lesson_title }}</span>
                            </div>
                        </div>
                        <span class="shrink-0 text-sm text-muted-foreground">Resolved {{ resolvedLabel(ticket) }}</span>
                    </Link>
                </div>

                <Pagination :links="resolvedTickets.meta.links" />
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
