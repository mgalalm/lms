<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import PingParentDialog from '@/components/teacher/PingParentDialog.vue';
import ProjectDetailDialog from '@/components/teacher/ProjectDetailDialog.vue';
import SupportTicketDialog from '@/components/teacher/SupportTicketDialog.vue';
import TeachingFeedItem from '@/components/teacher/TeachingFeedItem.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { onClickOutside } from '@vueuse/core';
import type { BreadcrumbItem, FeedbackTemplate, TeachingFeedGroup, TeachingFeedItemType } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarCheck2, Filter as FilterIcon } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    today: string;
    timezone: string;
    summary: { overdue: number; sessionsToday: number; projectsAwaitingReview: number };
    chipCounts: { all: number; sessions: number; projects: number; tasks: number };
    feed: TeachingFeedGroup[];
    filters: { search: string; from: string; to: string; featured: boolean; show_completed: boolean };
    feedbackTemplates: FeedbackTemplate[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Teaching', href: route('teaching.index') }];

const now = ref(Date.now());
const interval = setInterval(() => (now.value = Date.now()), 30_000);
onBeforeUnmount(() => clearInterval(interval));

function todayLabel(): string {
    return new Date(props.today).toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
}

const summaryLine = computed(() => {
    const segments: string[] = [];

    if (props.summary.overdue > 0) {
        segments.push(`${props.summary.overdue} overdue`);
    }
    if (props.summary.sessionsToday > 0) {
        segments.push(`${props.summary.sessionsToday} session${props.summary.sessionsToday === 1 ? '' : 's'} today`);
    }
    if (props.summary.projectsAwaitingReview > 0) {
        segments.push(`${props.summary.projectsAwaitingReview} project${props.summary.projectsAwaitingReview === 1 ? '' : 's'} awaiting review`);
    }

    return segments.join(' · ');
});

// Type chips: client-side only, filtering the already-loaded feed — no
// server round-trip. Selected state is written to the URL via
// history.replaceState (not an Inertia visit) purely so it's shareable
// and survives a refresh, per the "persist in the URL" requirement.
const CHIP_TYPES: { key: TeachingFeedItemType; label: string; countKey: keyof typeof props.chipCounts }[] = [
    { key: 'session', label: 'Sessions', countKey: 'sessions' },
    { key: 'project', label: 'Projects', countKey: 'projects' },
    { key: 'task', label: 'Tasks', countKey: 'tasks' },
];

const selectedTypes = ref<Set<TeachingFeedItemType>>(new Set());

onMounted(() => {
    const typesParam = new URLSearchParams(window.location.search).get('types');

    if (typesParam) {
        selectedTypes.value = new Set(typesParam.split(',').filter((t): t is TeachingFeedItemType => ['session', 'project', 'task'].includes(t)));
    }
});

function writeTypesToUrl(): void {
    const url = new URL(window.location.href);

    if (selectedTypes.value.size === 0) {
        url.searchParams.delete('types');
    } else {
        url.searchParams.set('types', Array.from(selectedTypes.value).join(','));
    }

    window.history.replaceState(window.history.state, '', url);
}

function selectAll(): void {
    selectedTypes.value = new Set();
    writeTypesToUrl();
}

function toggleType(type: TeachingFeedItemType): void {
    // Single-select: picking a type shows only that type, deselecting any
    // other. Picking the already-selected type toggles back to "All".
    const isOnlySelected = selectedTypes.value.size === 1 && selectedTypes.value.has(type);

    selectedTypes.value = isOnlySelected ? new Set() : new Set([type]);
    writeTypesToUrl();
}

const visibleFeed = computed<TeachingFeedGroup[]>(() => {
    if (selectedTypes.value.size === 0) {
        return props.feed;
    }

    return props.feed
        .map((group) => ({ ...group, items: group.items.filter((item) => selectedTypes.value.has(item.type)) }))
        .filter((group) => group.items.length > 0);
});

const hasAnyItems = computed(() => props.feed.some((group) => group.items.length > 0));
const hasVisibleItems = computed(() => visibleFeed.value.some((group) => group.items.length > 0));

// The single "next session" item — the earliest session in the whole
// (unfiltered) feed, used to give it the primary-tinted border.
const nextSessionId = computed(() => {
    for (const group of props.feed) {
        const session = group.items.find((item) => item.type === 'session');
        if (session) {
            return session.id;
        }
    }
    return null;
});

// Search + the "less-used" filters all hit the server (they change what's
// fetched), following the same debounce-on-empty idiom already used
// elsewhere in this codebase.
const search = ref(props.filters.search);
const from = ref(props.filters.from);
const to = ref(props.filters.to);
const featuredOnly = ref(props.filters.featured);
const showCompleted = ref(props.filters.show_completed);

function applyFilters(): void {
    router.get(
        route('teaching.index'),
        {
            search: search.value,
            from: from.value,
            to: to.value,
            featured: featuredOnly.value ? 1 : undefined,
            show_completed: showCompleted.value ? 1 : undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}

watch(search, (value, previous) => {
    if (value === '' && previous !== '') {
        applyFilters();
    }
});

const filtersOpen = ref(false);
const filtersRef = ref<HTMLElement | null>(null);
onClickOutside(filtersRef, () => (filtersOpen.value = false));

function applyFiltersAndClose(): void {
    filtersOpen.value = false;
    applyFilters();
}

// Dialog state — owned here, same pattern the old per-tab components used.
const supportDialogSessionId = ref<number | null>(null);
const pingParentDialog = ref<{ classSessionId: number; studentName: string } | null>(null);
const openProjectId = ref<number | null>(null);

function onProjectReviewed(): void {
    router.reload({ only: ['feed', 'summary', 'chipCounts'] });
}

function markTaskComplete(taskId: number): void {
    router.patch(route('teaching.tasks.complete', taskId), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Teaching" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h1 class="text-xl font-semibold">Teaching</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ todayLabel() }}<template v-if="summaryLine"> · {{ summaryLine }}</template>
                    </p>
                </div>
                <Link :href="route('teaching.archive')" class="text-sm text-primary hover:underline">Archived</Link>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <Button :variant="selectedTypes.size === 0 ? 'default' : 'outline'" size="sm" @click="selectAll">
                        All ({{ chipCounts.all }})
                    </Button>
                    <Button
                        v-for="chip in CHIP_TYPES"
                        :key="chip.key"
                        :variant="selectedTypes.has(chip.key) ? 'default' : 'outline'"
                        size="sm"
                        :aria-pressed="selectedTypes.has(chip.key)"
                        @click="toggleType(chip.key)"
                    >
                        {{ chip.label }} ({{ chipCounts[chip.countKey] }})
                    </Button>
                </div>

                <input
                    v-model="search"
                    type="search"
                    placeholder="Search by student name/ID, lesson, or project title"
                    class="min-w-64 rounded-md border border-input bg-background px-3 py-1.5 text-sm"
                    @keyup.enter="applyFilters()"
                />

                <div ref="filtersRef" class="relative">
                    <Button variant="outline" size="sm" @click="filtersOpen = !filtersOpen">
                        <FilterIcon class="size-4" />
                        Filters
                    </Button>

                    <div
                        v-if="filtersOpen"
                        class="absolute right-0 z-50 mt-1 w-72 space-y-3 rounded-md border border-sidebar-border/70 bg-popover p-3 shadow-md dark:border-sidebar-border"
                    >
                        <p class="text-xs font-medium text-muted-foreground">Project submission date range</p>
                        <div class="flex items-end gap-2">
                            <label class="flex flex-1 flex-col text-xs text-muted-foreground">
                                From
                                <input v-model="from" type="date" class="rounded-md border border-input bg-background px-2 py-1 text-sm" />
                            </label>
                            <label class="flex flex-1 flex-col text-xs text-muted-foreground">
                                To
                                <input v-model="to" type="date" class="rounded-md border border-input bg-background px-2 py-1 text-sm" />
                            </label>
                        </div>

                        <Label class="flex items-center gap-2 text-sm font-normal">
                            <Checkbox v-model:checked="featuredOnly" />
                            Featured projects only
                        </Label>

                        <Label class="flex items-center gap-2 text-sm font-normal">
                            <Checkbox v-model:checked="showCompleted" />
                            Show completed tasks
                        </Label>

                        <Button size="sm" class="w-full" @click="applyFiltersAndClose">Apply</Button>
                    </div>
                </div>
            </div>

            <EmptyState
                v-if="!hasAnyItems"
                :icon="CalendarCheck2"
                heading="You're all caught up"
                description="Nothing needs your attention right now — new sessions, project submissions, and tasks will show up here."
            />

            <p v-else-if="!hasVisibleItems" class="text-sm text-muted-foreground">No items match the selected filters.</p>

            <div v-else class="flex flex-col gap-6">
                <div v-for="group in visibleFeed" :key="group.key" class="space-y-2">
                    <h2 class="text-xs font-semibold tracking-wide text-muted-foreground uppercase">{{ group.label }}</h2>
                    <div
                        class="divide-y divide-sidebar-border/70 overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:divide-sidebar-border dark:border-sidebar-border"
                    >
                        <TeachingFeedItem
                            v-for="item in group.items"
                            :key="item.id"
                            :item="item"
                            :now="now"
                            :timezone="timezone"
                            :is-next="item.id === nextSessionId"
                            @open-project="openProjectId = $event"
                            @ping-parent="(classSessionId, studentName) => (pingParentDialog = { classSessionId, studentName })"
                            @open-support="supportDialogSessionId = $event"
                            @mark-task-complete="markTaskComplete"
                        />
                    </div>
                </div>
            </div>
        </div>

        <SupportTicketDialog
            v-if="supportDialogSessionId"
            :open="true"
            :class-session-id="supportDialogSessionId"
            @update:open="(value) => !value && (supportDialogSessionId = null)"
        />

        <PingParentDialog
            v-if="pingParentDialog"
            :open="true"
            :class-session-id="pingParentDialog.classSessionId"
            :student-name="pingParentDialog.studentName"
            @update:open="(value) => !value && (pingParentDialog = null)"
        />

        <ProjectDetailDialog
            :submission-id="openProjectId"
            :templates="feedbackTemplates"
            @close="openProjectId = null"
            @reviewed="onProjectReviewed"
        />
    </AppLayout>
</template>
