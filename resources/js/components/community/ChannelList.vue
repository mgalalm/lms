<script setup lang="ts">
import { relativeDate } from '@/composables/relativeDate';
import type { Channel } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Users } from 'lucide-vue-next';

defineProps<{
    channels: Channel[];
    selectedChannelId?: number;
}>();
</script>

<template>
    <div class="flex flex-col divide-y divide-sidebar-border/70 overflow-y-auto dark:divide-sidebar-border">
        <p v-if="!channels.length" class="p-4 text-sm text-muted-foreground">No channels yet.</p>

        <Link
            v-for="channel in channels"
            :key="channel.id"
            :href="route('community.show', channel.id)"
            class="flex items-start gap-2 p-3 text-left hover:bg-accent"
            :class="{ 'bg-accent': channel.id === selectedChannelId }"
        >
            <div class="flex min-w-0 flex-1 flex-col">
                <div class="flex items-center justify-between gap-2">
                    <span class="truncate text-sm font-medium">
                        {{ channel.name }}
                        <span v-if="channel.code" class="font-normal text-muted-foreground">({{ channel.code }})</span>
                    </span>
                    <span v-if="channel.last_message" class="shrink-0 text-xs text-muted-foreground">
                        {{ relativeDate(channel.last_message.created_at) }}
                    </span>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <span class="truncate text-xs text-muted-foreground">{{ channel.last_message?.preview ?? 'No messages yet' }}</span>
                    <span
                        v-if="channel.unread_count > 0"
                        class="flex h-4 min-w-4 shrink-0 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-medium text-primary-foreground"
                    >
                        {{ channel.unread_count }}
                    </span>
                </div>

                <div v-if="channel.type === 'group'" class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                    <Users class="size-3" />
                    {{ channel.member_count }} Members
                </div>
            </div>
        </Link>
    </div>
</template>
