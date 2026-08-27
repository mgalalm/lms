<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import LessonMaterialsMenu from '@/components/teacher/LessonMaterialsMenu.vue';
import PingParentDialog from '@/components/teacher/PingParentDialog.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCountdown } from '@/composables/useCountdown';
import { withDisplayName } from '@/composables/zoomJoinUrl';
import type { ClassSession, SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { CalendarClock } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        session: ClassSession | null;
        viewerRole: 'student' | 'teacher';
        timezone?: string;
    }>(),
    { timezone: 'UTC' },
);

const page = usePage<SharedData>();
const { countdown } = useCountdown(() => props.session?.scheduled_at);

const joinUrl = computed(() => {
    const url = props.viewerRole === 'teacher' ? props.session?.start_url : props.session?.join_url;

    return url ? withDisplayName(url, page.props.auth.user.name) : undefined;
});
const joinLabel = computed(() => (props.viewerRole === 'teacher' ? 'Join Now' : 'Join'));
const participant = computed(() => (props.viewerRole === 'teacher' ? props.session?.student : props.session?.teacher));
const participantRoleLabel = computed(() => (props.viewerRole === 'teacher' ? 'Student' : 'Teacher'));

const lessonPath = computed(() => {
    const lesson = props.session?.lesson;

    if (!lesson) {
        return '';
    }

    return [lesson.module?.course?.title, lesson.module?.title, lesson.title].filter(Boolean).join(' / ');
});

const pingParentDialogOpen = ref(false);

const scheduledLabel = computed(() => {
    if (!props.session) {
        return '';
    }

    return new Date(props.session.scheduled_at).toLocaleString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        timeZone: props.timezone,
    });
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-base">Next Session</CardTitle>
        </CardHeader>
        <CardContent>
            <EmptyState
                v-if="!session"
                :icon="CalendarClock"
                heading="No upcoming sessions"
                :description="
                    viewerRole === 'student'
                        ? 'You have no sessions scheduled right now. Buy a session package to get started.'
                        : 'You have no sessions scheduled right now.'
                "
                :cta-label="viewerRole === 'student' ? 'Buy a Session Package' : undefined"
                :cta-href="viewerRole === 'student' ? route('session-packages.index') : undefined"
            />

            <div v-else class="flex flex-col gap-4">
                <div v-if="countdown?.isPast" class="flex items-baseline gap-3">
                    <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-green-600 dark:text-green-500">
                        <span class="h-2 w-2 rounded-full bg-green-600 dark:bg-green-500" />
                        Live now
                    </span>
                    <div class="text-sm text-muted-foreground">{{ scheduledLabel }}</div>
                </div>
                <div v-else-if="countdown" class="flex items-baseline gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-semibold tabular-nums">{{ countdown.days }}</div>
                        <div class="text-xs uppercase text-muted-foreground">Days</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-semibold tabular-nums">{{ countdown.hours }}</div>
                        <div class="text-xs uppercase text-muted-foreground">Hrs</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-semibold tabular-nums">{{ countdown.minutes }}</div>
                        <div class="text-xs uppercase text-muted-foreground">Mins</div>
                    </div>
                    <div class="text-sm text-muted-foreground">{{ scheduledLabel }}</div>
                </div>

                <div class="text-sm">
                    <div class="font-medium">{{ lessonPath }}</div>
                    <div v-if="participant" class="text-muted-foreground">
                        {{ participantRoleLabel }}: {{ participant.name }}
                        <span v-if="participant.code">({{ participant.code }})</span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button v-if="joinUrl" as="a" :href="joinUrl" target="_blank" rel="noopener noreferrer" class="w-fit">
                        {{ joinLabel }}
                    </Button>
                    <Button v-else disabled class="w-fit">{{ joinLabel }}</Button>

                    <Button v-if="viewerRole === 'teacher'" variant="outline" class="w-fit" @click="pingParentDialogOpen = true">
                        Ping Parent
                    </Button>
                    <LessonMaterialsMenu v-if="viewerRole === 'teacher'" :lesson="session.lesson" />
                </div>
            </div>
        </CardContent>

        <PingParentDialog
            v-if="session && viewerRole === 'teacher'"
            v-model:open="pingParentDialogOpen"
            :class-session-id="session.id"
            :student-name="participant?.name ?? 'the parent'"
        />
    </Card>
</template>
