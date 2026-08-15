<script setup lang="ts">
import type { Message } from '@/types';
import { ClipboardList } from 'lucide-vue-next';

defineProps<{
    message: Message;
}>();
</script>

<template>
    <div class="flex max-w-full overflow-hidden rounded-md border border-border md:max-w-md">
        <div class="w-1 shrink-0" :style="{ backgroundColor: message.meta?.accent_color ?? '#2563eb' }" />
        <div class="min-w-0 flex-1 p-3">
            <div class="mb-1 flex items-center gap-2">
                <ClipboardList class="size-4 shrink-0 text-muted-foreground" />
                <span class="truncate text-sm font-semibold">{{ message.meta?.title }}</span>
            </div>
            <div v-if="message.body" class="tiptap-content break-words text-muted-foreground" v-html="message.body" />
        </div>
    </div>
</template>
