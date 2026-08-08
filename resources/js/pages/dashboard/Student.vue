<script setup lang="ts">
import BadgeList from '@/components/gamification/BadgeList.vue';
import LeaderboardList from '@/components/gamification/LeaderboardList.vue';
import NextSessionCard from '@/components/NextSessionCard.vue';
import SessionFeedbackModal from '@/components/SessionFeedbackModal.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Badge, BreadcrumbItem, ClassSession, LatestSessionReview, LeaderboardEntry } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Frown, Meh, Smile, Star } from 'lucide-vue-next';

defineProps<{
    nextSession: ClassSession | null;
    pendingFeedbackSession: ClassSession | null;
    latestReview: LatestSessionReview | null;
    points: number;
    recentBadges: Badge[];
    leaderboard: LeaderboardEntry[];
    timezone: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const sentimentIcon = { negative: Frown, neutral: Meh, positive: Smile } as const;
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <SessionFeedbackModal v-if="pendingFeedbackSession" :key="pendingFeedbackSession.id" :session="pendingFeedbackSession" />

        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <NextSessionCard :session="nextSession" viewer-role="student" :timezone="timezone" />

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
                <h2 class="text-sm font-semibold text-muted-foreground">Latest Session Review</h2>

                <div
                    v-if="latestReview"
                    class="flex items-center gap-3 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border"
                >
                    <component :is="sentimentIcon[latestReview.sentiment]" class="size-8 text-primary" />
                    <div>
                        <div class="flex items-center gap-0.5">
                            <Star
                                v-for="value in 5"
                                :key="value"
                                class="size-3.5"
                                :class="value <= latestReview.star_rating ? 'fill-primary text-primary' : 'text-muted-foreground'"
                            />
                        </div>
                        <p class="text-sm text-muted-foreground">{{ latestReview.lesson_title }} with {{ latestReview.teacher_name }}</p>
                    </div>
                </div>
                <div
                    v-else
                    class="flex items-center gap-2 rounded-xl border border-sidebar-border/70 bg-card p-4 text-sm text-muted-foreground dark:border-sidebar-border"
                >
                    <Star class="size-4" />
                    No review yet!
                </div>
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
