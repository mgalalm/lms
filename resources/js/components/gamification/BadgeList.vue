<script setup lang="ts">
import type { Badge } from '@/types';
import { Award, Clock, Flame, GraduationCap, type LucideIcon, Medal, Rocket, Star, Target, Trophy, Zap } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    badges: Badge[];
}>();

const icons: Record<string, LucideIcon> = {
    trophy: Trophy,
    star: Star,
    zap: Zap,
    award: Award,
    target: Target,
    flame: Flame,
    medal: Medal,
    rocket: Rocket,
    clock: Clock,
    'graduation-cap': GraduationCap,
};

function iconFor(key: string): LucideIcon {
    return icons[key] ?? Award;
}

const hasBadges = computed(() => props.badges.length > 0);
</script>

<template>
    <p v-if="!hasBadges" class="text-sm text-muted-foreground">No badges earned yet.</p>

    <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div
            v-for="badge in badges"
            :key="badge.id"
            class="flex items-start gap-3 rounded-xl border border-sidebar-border/70 bg-card p-3 dark:border-sidebar-border"
        >
            <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                <component :is="iconFor(badge.icon)" class="size-5" />
            </div>
            <div>
                <p class="text-sm font-medium">{{ badge.name }}</p>
                <p class="text-xs text-muted-foreground">{{ badge.description }}</p>
            </div>
        </div>
    </div>
</template>
