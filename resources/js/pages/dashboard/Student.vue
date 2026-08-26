<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import CurrentLessonCard from '@/components/dashboard/CurrentLessonCard.vue';
import MyProgressCard from '@/components/dashboard/MyProgressCard.vue';
import BadgeList from '@/components/gamification/BadgeList.vue';
import LeaderboardList from '@/components/gamification/LeaderboardList.vue';
import NextSessionCard from '@/components/NextSessionCard.vue';
import SessionFeedbackModal from '@/components/SessionFeedbackModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Badge, BreadcrumbItem, ClassSession, CurrentLesson, LatestSessionReview, LeaderboardEntry, LessonProgress, ProjectSubmission } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Coins, Frown, Meh, Smile, Star } from 'lucide-vue-next';

defineProps<{
    nextSession: ClassSession | null;
    pendingFeedbackSession: ClassSession | null;
    latestReview: LatestSessionReview | null;
    currentLesson: CurrentLesson | null;
    projectSubmissions: ProjectSubmission[];
    progress: LessonProgress | null;
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

        <div class="mx-auto max-w-7xl px-6 py-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="flex flex-col gap-6 lg:col-span-2">
                    <NextSessionCard :session="nextSession" viewer-role="student" :timezone="timezone" />
                    <CurrentLessonCard :lesson="currentLesson" :project-submissions="projectSubmissions" />
                    <MyProgressCard :progress="progress" />
                </div>

                <div class="flex flex-col gap-6">
                    <Card>
                        <CardHeader class="flex-row items-center justify-between">
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Coins class="size-5 text-[#F59E0B]" />
                                Coins
                            </CardTitle>
                            <Button as-child variant="outline" size="sm">
                                <Link :href="route('achievements.index')">How to Earn?</Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold text-[#F59E0B]">{{ points }} Coins</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Latest Session Review</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div v-if="latestReview" class="flex items-center gap-3">
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
                            <EmptyState
                                v-else
                                :icon="Star"
                                heading="No reviews yet"
                                description="Your feedback will show up here after you rate a completed session."
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex-row items-center justify-between">
                            <CardTitle class="text-base">Badges</CardTitle>
                            <Link :href="route('achievements.index')" class="text-xs text-primary hover:underline">View All</Link>
                        </CardHeader>
                        <CardContent>
                            <BadgeList :badges="recentBadges" :columns="1" />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex-row items-center justify-between">
                            <CardTitle class="text-base">Leaderboard</CardTitle>
                            <Link :href="route('achievements.index')" class="text-xs text-primary hover:underline">View All</Link>
                        </CardHeader>
                        <CardContent>
                            <LeaderboardList :entries="leaderboard" />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
