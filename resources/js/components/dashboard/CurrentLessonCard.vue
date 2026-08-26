<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import LessonProjectPanel from '@/components/dashboard/LessonProjectPanel.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { CurrentLesson, ProjectSubmission } from '@/types';
import { BookOpen, HelpCircle, Loader2, PackagePlus, Play, Video } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    lesson: CurrentLesson | null;
    projectSubmissions: ProjectSubmission[];
}>();

const lessonPath = computed(() => [props.lesson?.course_title, props.lesson?.module_title].filter(Boolean).join(' / '));

const activeTab = ref('recap');
const videoPlaybackUrl = ref<string | null>(null);
const videoTokenLoading = ref(false);
const videoTokenError = ref(false);

async function fetchVideoPlaybackUrl(): Promise<void> {
    if (!props.lesson) {
        return;
    }

    videoTokenLoading.value = true;
    videoTokenError.value = false;

    try {
        const response = await fetch(route('lessons.video.playback-token', props.lesson.id));

        if (!response.ok) {
            throw new Error('Failed to fetch video playback token');
        }

        const data = await response.json();
        videoPlaybackUrl.value = data.url;
    } catch {
        videoTokenError.value = true;
    } finally {
        videoTokenLoading.value = false;
    }
}

watch(activeTab, (tab) => {
    if (tab === 'video' && props.lesson?.video && !videoPlaybackUrl.value && !videoTokenLoading.value) {
        fetchVideoPlaybackUrl();
    }
});

watch(
    () => props.lesson?.id,
    () => {
        videoPlaybackUrl.value = null;
        videoTokenError.value = false;
    },
);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-base">Current Lesson</CardTitle>
            <CardDescription v-if="lesson">{{ lessonPath }}</CardDescription>
        </CardHeader>
        <CardContent>
            <EmptyState
                v-if="!lesson"
                :icon="PackagePlus"
                heading="No lesson in progress"
                description="Once you have a scheduled session, your current lesson will show up here."
                cta-label="Buy a Session Package"
                :cta-href="route('session-packages.index')"
            />

            <div v-else class="space-y-3">
                <p class="text-sm font-medium">{{ lesson.title }}</p>

                <Tabs v-model="activeTab">
                    <TabsList>
                        <TabsTrigger value="recap">Recap</TabsTrigger>
                        <TabsTrigger value="video">Video</TabsTrigger>
                        <TabsTrigger value="reading">Reading</TabsTrigger>
                        <TabsTrigger value="quiz">Quiz</TabsTrigger>
                        <TabsTrigger v-if="lesson.project" value="project">Project</TabsTrigger>
                    </TabsList>

                    <TabsContent value="recap">
                        <div class="flex aspect-video flex-col items-center justify-center gap-3 rounded-lg bg-muted">
                            <div class="flex size-12 items-center justify-center rounded-full bg-background/80 text-primary">
                                <Play class="size-6" />
                            </div>
                            <p class="text-xs text-muted-foreground">Session recap video</p>
                        </div>
                        <Button
                            v-if="lesson.recap"
                            as="a"
                            :href="route('sessions.recap', lesson.recap.class_session_id)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-3 w-fit"
                            size="sm"
                        >
                            Watch Recap
                        </Button>
                        <Button v-else disabled class="mt-3 w-fit" size="sm">Recap not available yet</Button>
                    </TabsContent>

                    <TabsContent value="video">
                        <div v-if="!lesson.video">
                            <EmptyState
                                :icon="Video"
                                heading="No teaching video yet"
                                description="Your teacher hasn't added a video for this lesson."
                            />
                        </div>
                        <div v-else-if="videoTokenLoading" class="flex aspect-video items-center justify-center rounded-lg bg-muted">
                            <Loader2 class="size-6 animate-spin text-muted-foreground" />
                        </div>
                        <div v-else-if="videoTokenError" class="flex aspect-video flex-col items-center justify-center gap-3 rounded-lg bg-muted">
                            <p class="text-xs text-muted-foreground">Couldn't load the video.</p>
                            <Button size="sm" variant="outline" @click="fetchVideoPlaybackUrl">Try Again</Button>
                        </div>
                        <iframe
                            v-else-if="videoPlaybackUrl"
                            :src="videoPlaybackUrl"
                            class="aspect-video w-full rounded-lg"
                            allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    </TabsContent>

                    <TabsContent value="reading">
                        <p v-if="lesson.reading_content" class="text-sm whitespace-pre-line text-muted-foreground">
                            {{ lesson.reading_content }}
                        </p>
                        <EmptyState
                            v-else
                            :icon="BookOpen"
                            heading="No reading material yet"
                            description="Your teacher hasn't added reading content for this lesson."
                        />
                    </TabsContent>

                    <TabsContent value="quiz">
                        <EmptyState
                            :icon="HelpCircle"
                            heading="No quiz available yet"
                            description="Check back after your next session — quizzes unlock as your teacher adds them."
                        />
                    </TabsContent>

                    <TabsContent v-if="lesson.project" value="project">
                        <LessonProjectPanel :project="lesson.project" :submissions="projectSubmissions" />
                    </TabsContent>
                </Tabs>
            </div>
        </CardContent>
    </Card>
</template>
