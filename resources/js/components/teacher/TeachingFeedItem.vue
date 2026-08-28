<script setup lang="ts">
import LessonMaterialsMenu from '@/components/teacher/LessonMaterialsMenu.vue';
import { Button } from '@/components/ui/button';
import { withDisplayName } from '@/composables/zoomJoinUrl';
import type { SharedData, TeachingFeedItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { CalendarClock, CheckCircle2, FolderCheck, ImageOff } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    item: TeachingFeedItem;
    now: number;
    timezone: string;
    isNext: boolean;
}>();

const emit = defineEmits<{
    openProject: [id: number];
    pingParent: [classSessionId: number, studentName: string];
    openSupport: [classSessionId: number];
    markTaskComplete: [taskId: number];
}>();

const page = usePage<SharedData>();

const TYPE_META = {
    session: { icon: CalendarClock, color: 'text-blue-600 dark:text-blue-400', label: 'Session' },
    project: { icon: FolderCheck, color: 'text-violet-600 dark:text-violet-400', label: 'Project' },
    task: { icon: CheckCircle2, color: 'text-amber-600 dark:text-amber-500', label: 'Task' },
} as const;

const TASK_TYPE_LABELS: Record<string, string> = {
    session_prep: 'session prep',
    session_review: 'session review',
    manual: 'manual',
};

const meta = computed(() => TYPE_META[props.item.type]);

function timeParts(dateStr: string): { date: Date; diffMinutes: number } {
    const date = new Date(dateStr);
    return { date, diffMinutes: (date.getTime() - props.now) / 60_000 };
}

function relativeFuture(dateStr: string): string {
    const { diffMinutes } = timeParts(dateStr);

    if (diffMinutes <= 0) {
        return 'in progress';
    }

    if (diffMinutes < 60) {
        return `starts in ${Math.round(diffMinutes)} min`;
    }

    if (diffMinutes < 24 * 60) {
        return `starts in ${Math.round(diffMinutes / 60)}h`;
    }

    return `starts ${new Date(dateStr).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })}`;
}

function relativePast(dateStr: string): string {
    const { diffMinutes } = timeParts(dateStr);
    const ago = Math.abs(diffMinutes);

    if (ago < 60) {
        return `${Math.max(1, Math.round(ago))} min ago`;
    }

    if (ago < 24 * 60) {
        return `${Math.round(ago / 60)}h ago`;
    }

    const days = Math.round(ago / (24 * 60));

    return `${days} day${days === 1 ? '' : 's'} ago`;
}

const kicker = computed(() => {
    const { type, at } = props.item;

    if (type === 'session') {
        return `Session · ${relativeFuture(at)}`;
    }

    if (type === 'project') {
        return `Project · submitted ${relativePast(at)}`;
    }

    const task = props.item.task!;

    return `Task · ${TASK_TYPE_LABELS[task.type] ?? task.type}`;
});

const title = computed(() => {
    if (props.item.type === 'session') {
        const lesson = props.item.session!.lesson;
        return [lesson.module?.course?.title, lesson.module?.title, lesson.title].filter(Boolean).join(' / ');
    }

    if (props.item.type === 'project') {
        return props.item.project!.title;
    }

    return props.item.task!.title;
});

const secondaryLine = computed(() => {
    if (props.item.type === 'session') {
        const student = props.item.session!.student;
        return student ? `${student.name}${student.code ? ` (${student.code})` : ''}` : null;
    }

    if (props.item.type === 'project') {
        const lesson = props.item.project!.lesson_project?.lesson;
        return lesson ? [lesson.module?.course?.title, lesson.module?.title, lesson.title].filter(Boolean).join(' / ') : null;
    }

    return props.item.task!.description;
});

const timestampLabel = computed(() => {
    const { type, at } = props.item;

    if (type === 'session') {
        return new Date(at).toLocaleString(undefined, {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            timeZone: props.timezone,
        });
    }

    return new Date(at).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
});

const isJoinable = computed(() => {
    if (props.item.type !== 'session') {
        return false;
    }

    const session = props.item.session!;

    if (session.status === 'in_progress') {
        return true;
    }

    const minutesUntilStart = (new Date(session.scheduled_at).getTime() - props.now) / 60_000;

    return minutesUntilStart <= 15 && minutesUntilStart >= -60;
});

const isImminent = computed(() => {
    if (props.item.type !== 'session') {
        return false;
    }

    return timeParts(props.item.session!.scheduled_at).diffMinutes <= 15;
});

const joinUrl = computed(() => {
    if (props.item.type !== 'session') {
        return undefined;
    }

    const url = props.item.session!.start_url;

    return url ? withDisplayName(url, page.props.auth.user.name) : undefined;
});

const screenshotThumbnails = computed(() => (props.item.type === 'project' ? (props.item.project!.screenshots ?? []).slice(0, 3) : []));
const extraScreenshotCount = computed(() =>
    props.item.type === 'project' ? Math.max(0, (props.item.project!.screenshots ?? []).length - 3) : 0,
);
</script>

<template>
    <div
        class="flex flex-col gap-3 border-l-4 p-4 sm:flex-row sm:items-start sm:justify-between"
        :class="[
            item.is_overdue ? 'border-destructive bg-destructive/5' : isNext ? 'border-primary bg-primary/5' : 'border-transparent',
        ]"
    >
        <div class="flex min-w-0 flex-1 items-start gap-3">
            <component :is="meta.icon" class="mt-0.5 size-5 shrink-0" :class="meta.color" />

            <div class="min-w-0 flex-1 space-y-0.5">
                <p class="text-xs font-medium text-muted-foreground">
                    {{ kicker }}
                    <span v-if="item.is_overdue" class="ml-1.5 rounded-full bg-destructive/10 px-1.5 py-0.5 text-destructive">Overdue</span>
                </p>
                <p class="truncate font-medium">{{ title }}</p>
                <p v-if="secondaryLine" class="truncate text-sm text-muted-foreground">{{ secondaryLine }}</p>

                <div v-if="item.type === 'project' && screenshotThumbnails.length" class="mt-2 flex items-center gap-1">
                    <img
                        v-for="shot in screenshotThumbnails"
                        :key="shot.id"
                        :src="shot.url"
                        class="size-10 rounded-md border border-sidebar-border/70 object-cover dark:border-sidebar-border"
                        alt=""
                    />
                    <span v-if="extraScreenshotCount > 0" class="text-xs text-muted-foreground">+{{ extraScreenshotCount }}</span>
                    <div v-if="!screenshotThumbnails.length" class="flex size-10 items-center justify-center rounded-md bg-muted">
                        <ImageOff class="size-4 text-muted-foreground" />
                    </div>
                </div>
            </div>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2 sm:flex-col sm:items-end">
            <span class="text-xs text-muted-foreground">{{ timestampLabel }}</span>

            <div class="flex flex-wrap items-center gap-2">
                <template v-if="item.type === 'session'">
                    <Button v-if="isJoinable" as="a" :href="joinUrl" target="_blank" rel="noopener noreferrer" size="sm" :variant="isImminent ? 'default' : 'outline'">
                        Join
                    </Button>
                    <LessonMaterialsMenu :lesson="item.session!.lesson" />
                    <Button
                        size="sm"
                        variant="outline"
                        @click="emit('pingParent', item.session!.id, item.session!.student?.name ?? 'the parent')"
                    >
                        Ping Parent
                    </Button>
                    <Button
                        v-if="item.session!.recap_url"
                        as="a"
                        :href="route('sessions.recap', item.session!.id)"
                        target="_blank"
                        rel="noopener noreferrer"
                        size="sm"
                        variant="ghost"
                    >
                        Recap
                    </Button>
                    <Link v-if="item.session!.latest_support_ticket" :href="route('teaching.support-tickets.show', item.session!.latest_support_ticket.id)">
                        <Button size="sm" variant="ghost">
                            {{ item.session!.latest_support_ticket.status === 'resolved' ? 'Ticket Resolved' : 'View Ticket' }}
                        </Button>
                    </Link>
                    <Button v-else size="sm" variant="ghost" @click="emit('openSupport', item.session!.id)">Support</Button>
                </template>

                <template v-else-if="item.type === 'project'">
                    <Button size="sm" @click="emit('openProject', item.project!.id)">Review</Button>
                </template>

                <template v-else>
                    <span
                        v-if="item.task!.type === 'session_prep'"
                        class="rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground"
                        title="Resolves automatically when you open this lesson's guide or slides"
                    >
                        Auto
                    </span>
                    <Button v-else-if="!item.task!.completed_at" size="sm" variant="outline" @click="emit('markTaskComplete', item.task!.id)">
                        Mark done
                    </Button>
                    <span v-else class="text-sm text-muted-foreground">Completed</span>
                </template>
            </div>
        </div>
    </div>
</template>
