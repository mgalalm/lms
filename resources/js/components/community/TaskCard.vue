<script setup lang="ts">
import type { Message } from '@/types';
import { ClipboardList } from 'lucide-vue-next';

defineProps<{
    message: Message;
}>();
</script>

<template>
    <div class="flex max-w-md overflow-hidden rounded-md border border-border">
        <div class="w-1 shrink-0" :style="{ backgroundColor: message.meta?.accent_color ?? '#2563eb' }" />
        <div class="flex-1 p-3">
            <div class="mb-1 flex items-center gap-2">
                <ClipboardList class="size-4 text-muted-foreground" />
                <span class="text-sm font-semibold">{{ message.meta?.title }}</span>
            </div>
            <div v-if="message.body" class="tiptap-content text-muted-foreground" v-html="message.body" />
        </div>
    </div>
</template>
