<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import LessonMaterialsMenu from '@/components/teacher/LessonMaterialsMenu.vue';
import SupportTicketDialog from '@/components/teacher/SupportTicketDialog.vue';
import { Button } from '@/components/ui/button';
import { withDisplayName } from '@/composables/zoomJoinUrl';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, ClassSession, Paginated, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps<{
    sessions: Paginated<ClassSession>;
    filter: 'today' | 'upcoming' | 'past';
    timezone: string;
}>();

const page = usePage<SharedData>();

const now = ref(Date.now());
const interval = setInterval(() => (now.value = Date.now()), 30_000);
onBeforeUnmount(() => clearInterval(interval));

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

function joinUrl(session: ClassSession): string | undefined {
    return session.start_url ? withDisplayName(session.start_url, page.props.auth.user.name) : undefined;
}

function isJoinable(session: ClassSession): boolean {
    if (session.status === 'in_progress') {
        return true;
    }

    const minutesUntilStart = (new Date(session.scheduled_at).getTime() - now.value) / 60_000;

    return minutesUntilStart <= 15 && minutesUntilStart >= -60;
}

const hasSessions = computed(() => props.sessions.data.length > 0);

function pingParent(session: ClassSession): void {
    router.post(route('teaching.sessions.ping-parent', session.id), {}, { preserveScroll: true });
}

const supportDialogSessionId = ref<number | null>(null);
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

                    <div class="flex items-center gap-2">
                        <Button
                            v-if="session.start_url && isJoinable(session)"
                            as="a"
                            :href="joinUrl(session)"
                            target="_blank"
                            rel="noopener noreferrer"
                            size="sm"
                        >
                            Join
                        </Button>
                        <Button v-else disabled size="sm">Join</Button>

                        <Link v-if="session.latest_support_ticket" :href="route('teaching.support-tickets.show', session.latest_support_ticket.id)">
                            <Button variant="outline" size="sm">
                                {{ session.latest_support_ticket.status === 'resolved' ? 'Ticket Resolved' : 'View Ticket' }}
                            </Button>
                        </Link>
                        <Button v-else variant="outline" size="sm" @click="supportDialogSessionId = session.id">Support</Button>

                        <Button variant="outline" size="sm" @click="pingParent(session)">Ping Parent</Button>

                        <LessonMaterialsMenu :lesson="session.lesson" />
                    </div>
                </div>
            </div>

            <Pagination :links="sessions.meta.links" />
        </div>

        <SupportTicketDialog
            v-if="supportDialogSessionId"
            :open="true"
            :class-session-id="supportDialogSessionId"
            @update:open="(value) => !value && (supportDialogSessionId = null)"
        />
    </AppLayout>
</template>
