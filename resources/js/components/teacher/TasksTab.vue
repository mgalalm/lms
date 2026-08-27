<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type { Paginated, TeacherTask } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { ClipboardCheck } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    tasks: Paginated<TeacherTask>;
    showCompleted: boolean;
}>();

const showCompleted = ref(props.showCompleted);

watch(showCompleted, (value) => {
    router.get(route('teaching.tasks.index'), { show_completed: value ? 1 : undefined }, { preserveState: true, preserveScroll: true });
});

const TYPE_LABELS: Record<TeacherTask['type'], string> = {
    session_prep: 'Session Prep',
    session_review: 'Session Review',
    manual: 'Manual',
};

function dueLabel(task: TeacherTask): string {
    return new Date(task.due_at).toLocaleString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function markComplete(taskId: number): void {
    router.patch(route('teaching.tasks.complete', taskId), {}, { preserveScroll: true });
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <Label class="flex w-fit items-center gap-2 text-sm font-normal">
            <Checkbox v-model:checked="showCompleted" />
            Show completed
        </Label>

        <EmptyState
            v-if="!tasks.data.length"
            :icon="ClipboardCheck"
            heading="Nothing to do right now"
            description="Session prep and review reminders, plus anything assigned to you, will show up here."
        />

        <div
            v-else
            class="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70 bg-card dark:divide-sidebar-border dark:border-sidebar-border"
        >
            <div v-for="task in tasks.data" :key="task.id" class="flex items-start justify-between gap-4 p-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{ task.title }}</span>
                        <span class="rounded-full bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">{{ TYPE_LABELS[task.type] }}</span>
                        <span v-if="task.is_overdue" class="rounded-full bg-destructive/10 px-1.5 py-0.5 text-xs font-medium text-destructive">
                            Overdue
                        </span>
                    </div>
                    <p v-if="task.description" class="text-sm text-muted-foreground">{{ task.description }}</p>
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <span>Due {{ dueLabel(task) }}</span>
                        <Link v-if="task.lesson" :href="route('lessons.tutor-guide', task.lesson.id)" class="text-primary hover:underline">
                            {{ task.lesson.title }}
                        </Link>
                    </div>
                </div>

                <div class="shrink-0">
                    <span v-if="task.completed_at" class="text-sm text-muted-foreground">Completed</span>
                    <p v-else-if="task.type === 'session_prep'" class="max-w-40 text-right text-xs text-muted-foreground">
                        Resolves automatically when you open this lesson's guide or slides
                    </p>
                    <Button v-else size="sm" variant="outline" @click="markComplete(task.id)">Mark complete</Button>
                </div>
            </div>
        </div>

        <Pagination :links="tasks.meta.links" />
    </div>
</template>
