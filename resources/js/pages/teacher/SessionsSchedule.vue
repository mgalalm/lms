<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, ClassSession, Paginated } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    sessions: Paginated<ClassSession>;
    filter: 'today' | 'upcoming' | 'past';
    timezone: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Sessions Schedule', href: route('teaching.sessions-schedule') },
];

const tabs: { key: 'today' | 'upcoming' | 'past'; label: string }[] = [
    { key: 'today', label: 'Today' },
    { key: 'upcoming', label: 'Upcoming' },
    { key: 'past', label: 'Past' },
];

function scheduledLabel(session: ClassSession): string {
    return new Date(session.scheduled_at).toLocaleString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        timeZone: props.timezone,
    });
}

function lessonPath(session: ClassSession): string {
    return [session.lesson.module?.course?.title, session.lesson.module?.title, session.lesson.title].filter(Boolean).join(' / ');
}

function isJoinable(session: ClassSession): boolean {
    if (session.status === 'in_progress') {
        return true;
    }

    const minutesUntilStart = (new Date(session.scheduled_at).getTime() - Date.now()) / 60_000;

    return minutesUntilStart <= 15 && minutesUntilStart >= -60;
}

const hasSessions = computed(() => props.sessions.data.length > 0);
</script>

<template>
    <Head title="Sessions Schedule" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex gap-2">
                <Link v-for="tab in tabs" :key="tab.key" :href="route('teaching.sessions-schedule', { filter: tab.key })" preserve-state>
                    <Button :variant="filter === tab.key ? 'default' : 'outline'" size="sm">{{ tab.label }}</Button>
                </Link>
            </div>

            <p v-if="!hasSessions" class="text-sm text-muted-foreground">Your sessions list is empty.</p>

            <div
                v-else
                class="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70 bg-card dark:divide-sidebar-border dark:border-sidebar-border"
            >
                <div v-for="session in sessions.data" :key="session.id" class="flex items-center justify-between gap-4 p-4">
                    <div>
                        <div class="font-medium">{{ lessonPath(session) }}</div>
                        <div class="text-sm text-muted-foreground">
                            {{ session.student?.name }}
                            <span v-if="session.student?.code">({{ session.student.code }})</span>
                            &middot; {{ scheduledLabel(session) }}
                        </div>
                    </div>

                    <Button
                        v-if="session.start_url && isJoinable(session)"
                        as="a"
                        :href="session.start_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        size="sm"
                    >
                        Join
                    </Button>
                    <Button v-else disabled size="sm">Join</Button>
                </div>
            </div>

            <Pagination :links="sessions.meta.links" />
        </div>
    </AppLayout>
</template>
