<script setup lang="ts">
import NextSessionCard from '@/components/NextSessionCard.vue';
import ProjectsTab from '@/components/teacher/ProjectsTab.vue';
import SessionsScheduleTab from '@/components/teacher/SessionsScheduleTab.vue';
import TasksTab from '@/components/teacher/TasksTab.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    BreadcrumbItem,
    ClassSession,
    FeedbackTemplate,
    Paginated,
    ProjectSubmission,
    TeacherTask,
} from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type TabKey = 'sessions' | 'projects' | 'tasks';

const props = defineProps<{
    activeTab: TabKey;
    today: string;
    nextSession: ClassSession | null;
    timezone: string;
    tabCounts: { pendingProjects: number; pendingTasks: number };
    // Sessions Schedule tab
    sessions?: Paginated<ClassSession>;
    filter?: 'today' | 'upcoming' | 'past';
    // Projects tab
    submissions?: Paginated<ProjectSubmission>;
    status?: 'submitted' | 'reviewed';
    projectCounts?: { pending: number; reviewed: number };
    filters?: { from: string; to: string; search: string; featured: boolean };
    feedbackTemplates?: FeedbackTemplate[];
    // Tasks tab
    tasks?: Paginated<TeacherTask>;
    showCompleted?: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Teaching', href: route('teaching.sessions-schedule') }];

const TAB_ROUTES: Record<TabKey, string> = {
    sessions: 'teaching.sessions-schedule',
    projects: 'teaching.projects.index',
    tasks: 'teaching.tasks.index',
};

const TAB_LABELS: Record<TabKey, string> = {
    sessions: 'Sessions Schedule',
    projects: 'Projects',
    tasks: 'Tasks',
};

const tabHrefs = ref<Record<TabKey, string>>({
    sessions: route(TAB_ROUTES.sessions),
    projects: route(TAB_ROUTES.projects),
    tasks: route(TAB_ROUTES.tasks),
});

const STORAGE_PREFIX = 'teaching-tab:';

onMounted(() => {
    // Remember this tab's current URL (with whatever filters are applied)
    // so switching away and back within the same browser session restores
    // it, and pick up any previously-remembered URLs for the other tabs.
    try {
        sessionStorage.setItem(STORAGE_PREFIX + props.activeTab, window.location.pathname + window.location.search);

        (Object.keys(TAB_ROUTES) as TabKey[]).forEach((tab) => {
            const remembered = sessionStorage.getItem(STORAGE_PREFIX + tab);

            if (remembered) {
                tabHrefs.value[tab] = remembered;
            }
        });
    } catch {
        // sessionStorage can throw in private-browsing/locked-down
        // contexts — falling back to the bare route is fine.
    }
});

function todayLabel(): string {
    return new Date(props.today).toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
}
</script>

<template>
    <Head title="Teaching" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h1 class="text-xl font-semibold">Teaching</h1>
                <p class="text-sm text-muted-foreground">{{ todayLabel() }}</p>
            </div>

            <NextSessionCard :session="nextSession" viewer-role="teacher" :timezone="timezone" />

            <div class="flex gap-2 border-b border-sidebar-border/70 pb-2 dark:border-sidebar-border">
                <Link v-for="tab in (Object.keys(TAB_ROUTES) as TabKey[])" :key="tab" :href="tabHrefs[tab]">
                    <Button :variant="activeTab === tab ? 'default' : 'outline'" size="sm">
                        {{ TAB_LABELS[tab] }}
                        <span
                            v-if="tab === 'projects' && tabCounts.pendingProjects > 0"
                            class="ml-1.5 rounded-full px-1.5 py-0.5 text-xs"
                            :class="activeTab === tab ? 'bg-primary-foreground/20' : 'bg-muted'"
                        >
                            {{ tabCounts.pendingProjects }}
                        </span>
                        <span
                            v-if="tab === 'tasks' && tabCounts.pendingTasks > 0"
                            class="ml-1.5 rounded-full px-1.5 py-0.5 text-xs"
                            :class="activeTab === tab ? 'bg-primary-foreground/20' : 'bg-muted'"
                        >
                            {{ tabCounts.pendingTasks }}
                        </span>
                    </Button>
                </Link>
            </div>

            <SessionsScheduleTab v-if="activeTab === 'sessions' && sessions && filter" :sessions="sessions" :filter="filter" :timezone="timezone" />

            <ProjectsTab
                v-else-if="activeTab === 'projects' && submissions && status && projectCounts && filters && feedbackTemplates"
                :submissions="submissions"
                :status="status"
                :project-counts="projectCounts"
                :filters="filters"
                :feedback-templates="feedbackTemplates"
            />

            <TasksTab v-else-if="activeTab === 'tasks' && tasks" :tasks="tasks" :show-completed="showCompleted ?? false" />
        </div>
    </AppLayout>
</template>
