<script setup lang="ts">
import TaskCard from '@/components/community/TaskCard.vue';
import type { Message } from '@/types';
import { Paperclip } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    message: Message;
    isOwnMessage: boolean;
}>();

const roleLabel = computed(() => {
    if (props.message.user.role === 'teacher') {
        return 'Tutor';
    }

    if (props.message.user.role === 'student') {
        return 'Student';
    }

    return props.message.user.role;
});

const timeLabel = computed(() =>
    new Date(props.message.created_at).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit', timeZone: 'UTC' }),
);
</script>

<template>
    <div class="flex flex-col gap-1" :class="isOwnMessage ? 'items-end' : 'items-start'">
        <div class="flex items-baseline gap-2 text-xs text-muted-foreground">
            <span class="font-medium text-foreground">{{ message.user.name }}</span>
            <span
                >{{ roleLabel }}<span v-if="message.user.code"> &middot; {{ message.user.code }}</span></span
            >
            <span>{{ timeLabel }}</span>
        </div>

        <TaskCard v-if="message.type === 'task_card'" :message="message" />
        <div v-else-if="message.body" class="tiptap-content max-w-md rounded-lg bg-muted px-3 py-2" v-html="message.body" />

        <div v-if="message.attachments?.length" class="flex flex-col gap-1">
            <a
                v-for="attachment in message.attachments"
                :key="attachment.path"
                :href="attachment.url"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-1.5 rounded-md border border-border px-2 py-1 text-xs hover:bg-accent"
            >
                <Paperclip class="size-3" />
                {{ attachment.filename }}
            </a>
        </div>
    </div>
</template>
