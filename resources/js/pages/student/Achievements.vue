<script setup lang="ts">
import BadgeList from '@/components/gamification/BadgeList.vue';
import LeaderboardList from '@/components/gamification/LeaderboardList.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Badge, BreadcrumbItem, LeaderboardEntry } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    points: number;
    badges: Badge[];
    leaderboard: LeaderboardEntry[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Achievements', href: route('achievements.index') }];
</script>

<template>
    <Head title="Achievements" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">Coins</p>
                <p class="text-2xl font-bold">{{ points }} Coins</p>
            </div>

            <div class="space-y-3">
                <h2 class="text-sm font-semibold text-muted-foreground">Badges</h2>
                <BadgeList :badges="badges" />
            </div>

            <div class="space-y-3">
                <h2 class="text-sm font-semibold text-muted-foreground">Leaderboard</h2>
                <LeaderboardList :entries="leaderboard" />
            </div>
        </div>
    </AppLayout>
</template>
