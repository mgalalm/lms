<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { CurrentLesson } from '@/types';
import { BookOpen, HelpCircle, PackagePlus, Play } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    lesson: CurrentLesson | null;
}>();

const lessonPath = computed(() => [props.lesson?.course_title, props.lesson?.module_title].filter(Boolean).join(' / '));
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

                <Tabs default-value="recap">
                    <TabsList>
                        <TabsTrigger value="recap">Recap</TabsTrigger>
                        <TabsTrigger value="reading">Reading</TabsTrigger>
                        <TabsTrigger value="quiz">Quiz</TabsTrigger>
                    </TabsList>

                    <TabsContent value="recap">
                        <div class="flex aspect-video flex-col items-center justify-center gap-3 rounded-lg bg-muted">
                            <div class="flex size-12 items-center justify-center rounded-full bg-background/80 text-primary">
                                <Play class="size-6" />
                            </div>
                            <p class="text-xs text-muted-foreground">Session recap video</p>
                        </div>
                        <Button class="mt-3 w-fit" size="sm">Watch Recap</Button>
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
                </Tabs>
            </div>
        </CardContent>
    </Card>
</template>
