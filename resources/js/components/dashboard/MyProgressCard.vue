<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { LessonProgress } from '@/types';
import { TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    progress: LessonProgress | null;
}>();

const percentage = computed(() => {
    if (!props.progress || props.progress.total === 0) {
        return 0;
    }

    return Math.round((props.progress.completed / props.progress.total) * 100);
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-base">My Progress</CardTitle>
        </CardHeader>
        <CardContent>
            <EmptyState
                v-if="!progress || progress.total === 0"
                :icon="TrendingUp"
                heading="No progress yet"
                description="Your module progress will appear here once you've had a session."
                cta-label="Buy a Session Package"
                :cta-href="route('session-packages.index')"
            />

            <div v-else class="space-y-2">
                <p class="text-sm font-medium">{{ progress.module_title }}</p>
                <div class="h-2 overflow-hidden rounded-full bg-muted">
                    <div class="h-full rounded-full bg-primary" :style="{ width: `${percentage}%` }" />
                </div>
                <p class="text-xs text-muted-foreground">{{ progress.completed }}/{{ progress.total }} lessons completed</p>
            </div>
        </CardContent>
    </Card>
</template>
