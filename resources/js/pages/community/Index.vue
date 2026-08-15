<script setup lang="ts">
import ChannelList from '@/components/community/ChannelList.vue';
import MessageBubble from '@/components/community/MessageBubble.vue';
import MessageComposer from '@/components/community/MessageComposer.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Channel, Message, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { ArrowLeft, Users } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    channels: Channel[];
    selectedChannel: Channel | null;
    messages: Message[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Community', href: route('community.index') }];

const page = usePage<SharedData>();
const viewerId = computed(() => page.props.auth.user.id);
const isTeacher = computed(() => page.props.auth.user.roles.includes('teacher'));

const messages = ref<Message[]>(props.messages);
watch(
    () => props.messages,
    (value) => (messages.value = value),
);

function dateHeading(value: string): string {
    return new Date(value).toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric', timeZone: 'UTC' });
}

const groupedMessages = computed(() => {
    const groups: { date: string; items: Message[] }[] = [];

    for (const message of messages.value) {
        const heading = dateHeading(message.created_at);
        const lastGroup = groups.at(-1);

        if (lastGroup?.date === heading) {
            lastGroup.items.push(message);
        } else {
            groups.push({ date: heading, items: [message] });
        }
    }

    return groups;
});

if (props.selectedChannel) {
    const channelId = props.selectedChannel.id;

    useEcho(`channel.${channelId}`, '.message.sent', (payload: { message: Message }) => {
        messages.value.push(payload.message);

        router.patch(route('community.read', channelId), {}, { preserveState: true, preserveScroll: true, only: ['channels'], showProgress: false });
    });
}
</script>

<template>
    <Head title="Community" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <div
                class="w-full shrink-0 border-sidebar-border/70 md:block md:w-72 md:border-r dark:border-sidebar-border"
                :class="selectedChannel ? 'hidden' : 'block'"
            >
                <ChannelList :channels="channels" :selected-channel-id="selectedChannel?.id" />
            </div>

            <div class="flex-1 flex-col overflow-hidden md:flex" :class="selectedChannel ? 'flex' : 'hidden'">
                <template v-if="selectedChannel">
                    <div class="flex items-center gap-2 border-b border-sidebar-border/70 p-3 dark:border-sidebar-border">
                        <Link :href="route('community.index')" class="text-muted-foreground hover:text-foreground md:hidden">
                            <ArrowLeft class="size-4" />
                        </Link>
                        <div>
                            <p class="text-sm font-semibold">
                                {{ selectedChannel.name }}
                                <span v-if="selectedChannel.code" class="font-normal text-muted-foreground">({{ selectedChannel.code }})</span>
                            </p>
                            <p v-if="selectedChannel.type === 'group'" class="flex items-center gap-1 text-xs text-muted-foreground">
                                <Users class="size-3" />
                                {{ selectedChannel.member_count }} Members
                            </p>
                        </div>
                    </div>

                    <div class="flex-1 space-y-4 overflow-y-auto p-4">
                        <div v-for="group in groupedMessages" :key="group.date" class="space-y-3">
                            <div class="flex items-center justify-center">
                                <span class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">{{ group.date }}</span>
                            </div>
                            <MessageBubble
                                v-for="message in group.items"
                                :key="message.id"
                                :message="message"
                                :is-own-message="message.user.id === viewerId"
                            />
                        </div>
                        <p v-if="!messages.length" class="text-center text-sm text-muted-foreground">No messages yet — say hello!</p>
                    </div>

                    <MessageComposer :channel-id="selectedChannel.id" :can-send-task-card="isTeacher" @sent="() => {}" />
                </template>

                <div v-else class="flex flex-1 items-center justify-center text-sm text-muted-foreground">Select a channel</div>
            </div>
        </div>
    </AppLayout>
</template>
