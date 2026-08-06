<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
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
import { ref } from 'vue';

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

const flagTabs: { key: 'all' | FlagSeverity; label: string }[] = [
    { key: 'all', label: 'All' },
    { key: 'green', label: 'Green Flags' },
    { key: 'yellow', label: 'Yellow Flags' },
    { key: 'red', label: 'Red Flags' },
];

const flagPillClasses: Record<FlagSeverity, string> = {
    green: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
    yellow: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    red: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
};

function applyRange(): void {
    router.get(route('insights.index'), { from: from.value, to: to.value, flag: props.flag, search: search.value }, { preserveState: true });
}

function applySearch(): void {
    router.get(route('insights.index'), { from: from.value, to: to.value, flag: props.flag, search: search.value }, { preserveState: true });
}

function setPreset(days: number): void {
    const end = new Date();
    const start = new Date();
    start.setDate(end.getDate() - (days - 1));

    from.value = start.toISOString().slice(0, 10);
    to.value = end.toISOString().slice(0, 10);
    applyRange();
}

function scheduledLabel(value: string): string {
    return new Date(value).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

const totalFlags = props.flagsBreakdown.green + props.flagsBreakdown.yellow + props.flagsBreakdown.red;
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

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                    <p class="text-xs text-muted-foreground">Objections</p>
                    <p class="text-xl font-semibold">{{ metrics.objections }}</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                    <p class="text-xs text-muted-foreground">Flags</p>
                    <p class="text-xl font-semibold">{{ totalFlags }}</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                    <p class="text-xs text-muted-foreground">Excuses</p>
                    <p class="text-xl font-semibold">{{ metrics.excuses }}</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                    <p class="text-xs text-muted-foreground">Study Level</p>
                    <p class="text-xl font-semibold">{{ metrics.study_level }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                    <p class="text-xs text-muted-foreground">Check-in on Time</p>
                    <p class="text-xl font-semibold">{{ attendance.check_in.on_time }}</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                    <p class="text-xs text-muted-foreground">Check-in Late</p>
                    <p class="text-xl font-semibold">{{ attendance.check_in.late }}</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                    <p class="text-xs text-muted-foreground">Join Sessions on Time</p>
                    <p class="text-xl font-semibold">{{ attendance.join.on_time }}</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                    <p class="text-xs text-muted-foreground">Join Sessions Late</p>
                    <p class="text-xl font-semibold">{{ attendance.join.late }}</p>
                </div>
            </div>

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

            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 text-center dark:border-sidebar-border">
                    <p class="text-xs text-muted-foreground">Green</p>
                    <p class="text-xl font-semibold text-green-600 dark:text-green-400">{{ flagsBreakdown.green }}</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 text-center dark:border-sidebar-border">
                    <p class="text-xs text-muted-foreground">Yellow</p>
                    <p class="text-xl font-semibold text-amber-600 dark:text-amber-400">{{ flagsBreakdown.yellow }}</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 text-center dark:border-sidebar-border">
                    <p class="text-xs text-muted-foreground">Red</p>
                    <p class="text-xl font-semibold text-red-600 dark:text-red-400">{{ flagsBreakdown.red }}</p>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex gap-2">
                        <Link
                            v-for="tab in flagTabs"
                            :key="tab.key"
                            :href="route('insights.index', { from, to, flag: tab.key, search })"
                            preserve-state
                        >
                            <Button :variant="flag === tab.key ? 'default' : 'outline'" size="sm">{{ tab.label }}</Button>
                        </Link>
                    </div>

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
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="flagPillClasses[session.flag_severity]">
                                {{ session.flag_severity }}
                            </span>
                        </div>
                    </div>
                </div>

                <Pagination :links="reviewedSessions.meta.links" />
            </div>
        </div>
    </AppLayout>
</template>
