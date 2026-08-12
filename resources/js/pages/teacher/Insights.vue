<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    BreadcrumbItem,
    FlagSeverity,
    InsightsAttendance,
    InsightsFlagsBreakdown,
    InsightsMetrics,
    InsightsPerformance,
    InsightsProfile,
    Paginated,
    ReviewedSession,
} from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, CircleCheckBig, Clock, Flag, Hourglass, MessageCircleQuestion, MessageSquareWarning, Video } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    range: { from: string; to: string };
    flag: 'all' | FlagSeverity;
    search: string;
    profile: InsightsProfile;
    metrics: InsightsMetrics;
    attendance: InsightsAttendance;
    performance: InsightsPerformance;
    flagsBreakdown: InsightsFlagsBreakdown;
    reviewedSessions: Paginated<ReviewedSession>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Insights', href: route('insights.index') }];

const from = ref(props.range.from);
const to = ref(props.range.to);
const search = ref(props.search);

const categoryLabels: Record<string, string> = {
    feedback_score: 'Feedback',
    attitude_score: 'Attitude',
    preparation_score: 'Preparation',
    curriculum_score: 'Curriculum',
    teaching_score: 'Teaching',
    setup_score: 'Setup',
};

const flagTabs: { key: 'all' | FlagSeverity; emoji: string | null }[] = [
    { key: 'all', emoji: null },
    { key: 'green', emoji: '😊' },
    { key: 'yellow', emoji: '😐' },
    { key: 'red', emoji: '😟' },
];

const flagEmoji: Record<FlagSeverity, string> = {
    green: '😊',
    yellow: '😐',
    red: '😟',
};

// Native emoji render as a fixed yellow glyph; hue-rotate shifts that
// toward green/red so the faces themselves read as the flag color.
const flagFilter: Record<FlagSeverity, string> = {
    green: 'hue-rotate(80deg) saturate(1.6)',
    yellow: 'none',
    red: 'hue-rotate(-50deg) saturate(1.6)',
};

const flagLabel: Record<FlagSeverity, string> = {
    green: 'Green Flag',
    yellow: 'Yellow Flag',
    red: 'Red Flag',
};

function applyRange(): void {
    router.get(
        route('insights.index'),
        { from: from.value, to: to.value, flag: props.flag, search: search.value },
        { preserveState: true, preserveScroll: true },
    );
}

function applySearch(): void {
    router.get(
        route('insights.index'),
        { from: from.value, to: to.value, flag: props.flag, search: search.value },
        { preserveState: true, preserveScroll: true },
    );
}

// The native search input's clear (X) button — and just backspacing to
// empty — doesn't fire keyup.enter, so the stale filtered list would
// otherwise stick around until the user explicitly searches again.
watch(search, (value, previous) => {
    if (value === '' && previous !== '') {
        applySearch();
    }
});

function setPreset(days: number): void {
    const end = new Date();
    const start = new Date();
    start.setDate(end.getDate() - (days - 1));

    from.value = start.toISOString().slice(0, 10);
    to.value = end.toISOString().slice(0, 10);
    applyRange();
}

function scheduledLabel(value: string): string {
    return new Date(value).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', timeZone: 'UTC' });
}

const totalFlags = props.flagsBreakdown.green + props.flagsBreakdown.yellow + props.flagsBreakdown.red;

const topMetrics = [
    { name: 'Objections', stat: props.metrics.objections, icon: MessageSquareWarning },
    { name: 'Flags', stat: totalFlags, icon: Flag },
    { name: 'Excuses', stat: props.metrics.excuses, icon: MessageCircleQuestion },
    { name: 'Study Level', stat: props.metrics.study_level, icon: BookOpen },
];

const attendanceStats = [
    { name: 'Check-in on Time', stat: props.attendance.check_in.on_time, icon: CircleCheckBig },
    { name: 'Check-in Late', stat: props.attendance.check_in.late, icon: Clock },
    { name: 'Join Sessions on Time', stat: props.attendance.join.on_time, icon: Video },
    { name: 'Join Sessions Late', stat: props.attendance.join.late, icon: Hourglass },
];
</script>

<template>
    <Head title="Insights" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex gap-4">
                    <div class="rounded-xl border border-sidebar-border/70 bg-card px-4 py-3 dark:border-sidebar-border">
                        <p class="text-xs text-muted-foreground">Sessions</p>
                        <p class="text-lg font-semibold">{{ profile.sessions_taught }}</p>
                    </div>
                    <div class="rounded-xl border border-sidebar-border/70 bg-card px-4 py-3 dark:border-sidebar-border">
                        <p class="text-xs text-muted-foreground">Certificates</p>
                        <p class="text-lg font-semibold">{{ profile.certificates_earned }}</p>
                    </div>
                    <div class="rounded-xl border border-sidebar-border/70 bg-card px-4 py-3 dark:border-sidebar-border">
                        <p class="text-xs text-muted-foreground">Points</p>
                        <p class="text-lg font-semibold">{{ profile.points }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-end gap-2">
                    <Button variant="outline" size="sm" @click="setPreset(7)">Last 7 days</Button>
                    <Button variant="outline" size="sm" @click="setPreset(30)">Last 30 days</Button>
                    <div class="flex items-end gap-2">
                        <label class="flex flex-col text-xs text-muted-foreground">
                            From
                            <input v-model="from" type="date" class="fi-input rounded-md border border-input bg-background px-2 py-1 text-sm" />
                        </label>
                        <label class="flex flex-col text-xs text-muted-foreground">
                            To
                            <input v-model="to" type="date" class="fi-input rounded-md border border-input bg-background px-2 py-1 text-sm" />
                        </label>
                        <Button size="sm" @click="applyRange">Apply</Button>
                    </div>
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div
                    v-for="item in topMetrics"
                    :key="item.name"
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card px-4 pb-4 pt-5 dark:border-sidebar-border"
                >
                    <dt>
                        <div class="absolute rounded-md bg-primary p-2">
                            <component :is="item.icon" class="size-5 text-primary-foreground" aria-hidden="true" />
                        </div>
                        <p class="ml-12 truncate text-xs text-muted-foreground">{{ item.name }}</p>
                    </dt>
                    <dd class="ml-12">
                        <p class="text-xl font-semibold">{{ item.stat }}</p>
                    </dd>
                </div>
            </dl>

            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div
                    v-for="item in attendanceStats"
                    :key="item.name"
                    class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card px-4 pb-4 pt-5 dark:border-sidebar-border"
                >
                    <dt>
                        <div class="absolute rounded-md bg-primary p-2">
                            <component :is="item.icon" class="size-5 text-primary-foreground" aria-hidden="true" />
                        </div>
                        <p class="ml-12 truncate text-xs text-muted-foreground">{{ item.name }}</p>
                    </dt>
                    <dd class="ml-12">
                        <p class="text-xl font-semibold">{{ item.stat }}</p>
                    </dd>
                </div>
            </dl>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="space-y-3 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border lg:col-span-2">
                    <h2 class="text-sm font-semibold text-muted-foreground">Performance Insights</h2>
                    <div v-for="(score, category) in performance.categories" :key="category" class="space-y-1">
                        <div class="flex items-center justify-between text-sm">
                            <span>{{ categoryLabels[category] ?? category }}</span>
                            <span class="text-muted-foreground">{{ score }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div class="h-full rounded-full bg-primary" :style="{ width: `${score}%` }" />
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-col items-center justify-center gap-2 rounded-xl border border-sidebar-border/70 bg-card p-4 text-center dark:border-sidebar-border"
                >
                    <p class="text-xs text-muted-foreground">Total Score</p>
                    <p class="text-4xl font-bold">{{ performance.total_score }}%</p>
                    <p v-if="performance.total_score >= 90" class="text-sm text-green-600 dark:text-green-400">👏 Great work!</p>
                </div>
            </div>

            <TooltipProvider :delay-duration="0">
                <div class="grid grid-cols-3 gap-3">
                    <div
                        v-for="severity in ['green', 'yellow', 'red'] as const"
                        :key="severity"
                        class="flex flex-col items-center gap-1 rounded-xl border border-sidebar-border/70 bg-card p-4 text-center dark:border-sidebar-border"
                    >
                        <Tooltip>
                            <TooltipTrigger>
                                <span class="text-4xl leading-none" :style="{ filter: flagFilter[severity] }">{{ flagEmoji[severity] }}</span>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{{ flagLabel[severity] }}</p>
                            </TooltipContent>
                        </Tooltip>
                        <p class="text-xl font-semibold">{{ flagsBreakdown[severity] }}</p>
                    </div>
                </div>
            </TooltipProvider>

            <div class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <TooltipProvider :delay-duration="0">
                        <div class="flex items-center gap-2">
                            <Link
                                v-for="tab in flagTabs"
                                :key="tab.key"
                                :href="route('insights.index', { from, to, flag: tab.key, search })"
                                preserve-state
                                preserve-scroll
                            >
                                <Button v-if="!tab.emoji" :variant="flag === tab.key ? 'default' : 'outline'" size="sm">All</Button>
                                <Tooltip v-else>
                                    <TooltipTrigger as-child>
                                        <Button :variant="flag === tab.key ? 'default' : 'outline'" size="sm" class="text-lg">
                                            <span :style="{ filter: flagFilter[tab.key as FlagSeverity] }">{{ tab.emoji }}</span>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{{ flagLabel[tab.key as FlagSeverity] }}s</p>
                                    </TooltipContent>
                                </Tooltip>
                            </Link>
                        </div>
                    </TooltipProvider>

                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search by Student ID/Session Name"
                        class="fi-input rounded-md border border-input bg-background px-3 py-1.5 text-sm"
                        @keyup.enter="applySearch"
                    />
                </div>

                <p v-if="reviewedSessions.data.length === 0" class="text-sm text-muted-foreground">No reviewed sessions in this range.</p>

                <div
                    v-else
                    class="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70 bg-card dark:divide-sidebar-border dark:border-sidebar-border"
                >
                    <div v-for="session in reviewedSessions.data" :key="session.id" class="flex items-center justify-between gap-4 p-4">
                        <div>
                            <p class="font-medium">{{ session.lesson_title }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ session.student?.name }}
                                <span v-if="session.student?.code">({{ session.student.code }})</span>
                                &middot; {{ scheduledLabel(session.scheduled_at) }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-sm text-muted-foreground">{{ session.total_score }}%</span>
                            <TooltipProvider :delay-duration="0">
                                <Tooltip>
                                    <TooltipTrigger>
                                        <span class="text-2xl leading-none" :style="{ filter: flagFilter[session.flag_severity] }">{{
                                            flagEmoji[session.flag_severity]
                                        }}</span>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{{ flagLabel[session.flag_severity] }}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>
                    </div>
                </div>

                <Pagination :links="reviewedSessions.meta.links" />
            </div>
        </div>
    </AppLayout>
</template>
