<script setup lang="ts">
import MessageBubble from '@/components/community/MessageBubble.vue';
import MessageComposer from '@/components/community/MessageComposer.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Message, SharedData, SupportTicket } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    ticket: SupportTicket;
    messages: Message[];
}>();

const page = usePage<SharedData>();
const viewerId = computed(() => page.props.auth.user.id);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Teaching', href: route('teaching.index') },
    { title: props.ticket.code, href: route('teaching.support-tickets.show', props.ticket.id) },
];

const messages = ref<Message[]>(props.messages);

useEcho(`channel.${props.ticket.channel_id}`, '.message.sent', (payload: { message: Message }) => {
    // A moderated message can reach this client twice — once from the
    // initial page load (the sender always sees their own message) and
    // again from the broadcast once it clears moderation. Replace the
    // existing entry instead of duplicating it (see community/Index.vue).
    const existingIndex = messages.value.findIndex((message) => message.id === payload.message.id);

    if (existingIndex === -1) {
        messages.value.push(payload.message);
    } else {
        messages.value[existingIndex] = payload.message;
    }
});

function resolve(): void {
    router.patch(route('teaching.support-tickets.resolve', props.ticket.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Ticket ${ticket.code}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col overflow-hidden">
            <div class="flex items-center justify-between border-b border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold">#{{ ticket.code }}</span>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="
                                ticket.status === 'resolved'
                                    ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-400'
                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400'
                            "
                        >
                            {{ ticket.status === 'resolved' ? 'Resolved' : 'Open' }}
                        </span>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ ticket.subject }}
                        <span v-if="ticket.class_session?.lesson_title"> &middot; {{ ticket.class_session.lesson_title }}</span>
                    </p>
                </div>

                <Button v-if="ticket.status === 'open'" variant="outline" size="sm" @click="resolve">Resolve</Button>
            </div>

            <div class="flex-1 space-y-3 overflow-y-auto p-4">
                <MessageBubble v-for="message in messages" :key="message.id" :message="message" :is-own-message="message.user.id === viewerId" />
            </div>

            <MessageComposer v-if="ticket.status === 'open'" :channel-id="ticket.channel_id" @sent="() => {}" />
            <p v-else class="border-t border-sidebar-border/70 p-4 text-center text-sm text-muted-foreground dark:border-sidebar-border">
                This ticket has been resolved.
            </p>
        </div>
    </AppLayout>
</template>
