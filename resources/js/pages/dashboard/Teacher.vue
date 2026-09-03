<script setup lang="ts">
import NextSessionCard from '@/components/NextSessionCard.vue';
import LeaderboardList from '@/components/gamification/LeaderboardList.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, ClassSession, LeaderboardEntry, SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useEchoNotification } from '@laravel/echo-vue';

defineProps<{
    nextSession: ClassSession | null;
    points: number;
    leaderboard: LeaderboardEntry[];
    timezone: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const page = usePage<SharedData>();

// A newly scheduled session may become the next session shown here —
// re-fetch it without requiring a manual refresh.
useEchoNotification(
    `App.Models.User.${page.props.auth.user.id}`,
    () => router.reload({ only: ['nextSession'] }),
    'class-session.scheduled',
);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <NextSessionCard :session="nextSession" viewer-role="teacher" :timezone="timezone" />

            <Button as-child variant="outline" class="w-fit">
                <Link :href="route('teaching.index')">Go to Teaching</Link>
            </Button>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">Points</p>
                <p class="text-2xl font-bold">{{ points }}</p>
            </div>

            <div class="space-y-3">
                <h2 class="text-sm font-semibold text-muted-foreground">Leaderboard</h2>
                <LeaderboardList :entries="leaderboard" />
            </div>
        </div>
    </AppLayout>
</template>
