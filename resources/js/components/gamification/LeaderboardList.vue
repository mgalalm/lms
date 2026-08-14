<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import type { LeaderboardEntry } from '@/types';
import { Trophy } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    entries: LeaderboardEntry[];
}>();

const hasEntries = computed(() => props.entries.length > 0);
</script>

<template>
    <EmptyState v-if="!hasEntries" :icon="Trophy" heading="No rankings yet" description="Once points are earned, the leaderboard will show up here." />

    <div
        v-else
        class="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70 bg-card dark:divide-sidebar-border dark:border-sidebar-border"
    >
        <div
            v-for="entry in entries"
            :key="entry.id"
            class="flex items-center justify-between gap-4 p-3"
            :class="entry.is_current_user ? 'bg-primary/5' : ''"
        >
            <div class="flex items-center gap-3">
                <span class="w-6 text-sm font-medium text-muted-foreground">#{{ entry.rank }}</span>
                <span class="flex size-8 items-center justify-center rounded-full bg-muted text-xs font-medium">
                    {{ entry.name.charAt(0).toUpperCase() }}
                </span>
                <span class="text-sm font-medium">
                    {{ entry.name }}
                    <span v-if="entry.is_current_user" class="text-xs text-muted-foreground">(Me)</span>
                </span>
            </div>

            <span class="text-sm font-semibold">{{ entry.points }}</span>
        </div>
    </div>
</template>
