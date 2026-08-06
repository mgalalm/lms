<script setup lang="ts">
import NextSessionCard from '@/components/NextSessionCard.vue';
import BadgeList from '@/components/gamification/BadgeList.vue';
import LeaderboardList from '@/components/gamification/LeaderboardList.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Badge, BreadcrumbItem, ClassSession, LeaderboardEntry } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    nextSession: ClassSession | null;
    points: number;
    recentBadges: Badge[];
    leaderboard: LeaderboardEntry[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <NextSessionCard :session="nextSession" viewer-role="student" />

            <div class="flex items-center justify-between gap-2 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                <div>
                    <p class="text-xs text-muted-foreground">Coins</p>
                    <p class="text-2xl font-bold">{{ points }} Coins</p>
                </div>
                <Button as-child variant="outline" size="sm">
                    <Link :href="route('achievements.index')">How to Earn?</Link>
                </Button>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-muted-foreground">Badges</h2>
                    <Link :href="route('achievements.index')" class="text-xs text-primary hover:underline">View All</Link>
                </div>
                <BadgeList :badges="recentBadges" />
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-muted-foreground">Leaderboard</h2>
                    <Link :href="route('achievements.index')" class="text-xs text-primary hover:underline">View All</Link>
                </div>
                <LeaderboardList :entries="leaderboard" />
            </div>
        </div>
    </AppLayout>
</template>
