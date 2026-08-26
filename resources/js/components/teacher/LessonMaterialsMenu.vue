<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { Lesson } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, ClipboardList, GraduationCap, Play, Presentation } from 'lucide-vue-next';

const props = defineProps<{ lesson: Lesson }>();

const courseId = props.lesson.module?.course?.id;
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm">
                <BookOpen class="size-4" />
                Lesson
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuLabel class="max-w-64 truncate text-xs font-normal text-muted-foreground">{{ lesson.title }}</DropdownMenuLabel>
            <DropdownMenuItem as-child>
                <Link class="flex w-full items-center gap-2" :href="route('lessons.tutor-guide', lesson.id)">
                    <ClipboardList class="size-4" />
                    Tutor Guide
                </Link>
            </DropdownMenuItem>

            <template v-if="courseId">
                <DropdownMenuSeparator />
                <DropdownMenuItem as-child>
                    <Link
                        class="flex w-full items-center gap-2"
                        :href="route('lms.show', { course: courseId, lesson: lesson.id, open: 'video' })"
                    >
                        <Play class="size-4" />
                        Watch Video
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                    <Link
                        class="flex w-full items-center gap-2"
                        :href="route('lms.show', { course: courseId, lesson: lesson.id, open: 'slideshow' })"
                    >
                        <Presentation class="size-4" />
                        View Slideshow
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                    <Link class="flex w-full items-center gap-2" :href="route('lms.show', { course: courseId, open: 'quiz' })">
                        <GraduationCap class="size-4" />
                        Certification Quiz
                    </Link>
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
