<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, TutorGuideGlossaryTerm, TutorGuideProcedureStep } from '@/types';
import { Head } from '@inertiajs/vue3';
import { BookOpen } from 'lucide-vue-next';

const props = defineProps<{
    lesson: { id: number; title: string };
    introHtml: string | null;
    procedureSteps: TutorGuideProcedureStep[];
    glossaryTerms: TutorGuideGlossaryTerm[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'LMS', href: route('lms.index') },
    { title: props.lesson.title, href: '#' },
    { title: 'Tutor Guide', href: '#' },
];

const phaseStyles: Record<TutorGuideProcedureStep['phase'], string> = {
    explore: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    evaluate: 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300',
};

const phaseLabels: Record<TutorGuideProcedureStep['phase'], string> = {
    explore: 'Explore',
    evaluate: 'Evaluate',
};
</script>

<template>
    <Head :title="`Tutor Guide — ${lesson.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-lg font-semibold">Tutor Guide</h1>
                <p class="text-sm text-muted-foreground">{{ lesson.title }} — staff-only prep material, never shown to students.</p>
            </div>

            <EmptyState
                v-if="!introHtml && procedureSteps.length === 0 && glossaryTerms.length === 0"
                :icon="BookOpen"
                heading="No tutor guide yet"
                description="Add an intro, procedure steps, and glossary terms for this lesson from the admin panel."
            />

            <template v-else>
                <div v-if="introHtml" class="space-y-3">
                    <h2 class="text-sm font-semibold text-muted-foreground">Intro</h2>
                    <div
                        class="prose prose-sm dark:prose-invert max-w-none rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border"
                        v-html="introHtml"
                    />
                </div>

                <div v-if="procedureSteps.length > 0" class="space-y-3">
                    <h2 class="text-sm font-semibold text-muted-foreground">Procedure Steps</h2>
                    <ol class="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70 bg-card dark:divide-sidebar-border dark:border-sidebar-border">
                        <li v-for="(step, index) in procedureSteps" :key="index" class="flex items-center justify-between gap-4 p-4">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-muted-foreground">{{ index + 1 }}.</span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="phaseStyles[step.phase]">
                                    {{ phaseLabels[step.phase] }}
                                </span>
                                <p class="text-sm font-medium">{{ step.title }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground">
                                {{ step.duration_minutes }} min
                            </span>
                        </li>
                    </ol>
                </div>

                <div v-if="glossaryTerms.length > 0" class="space-y-3">
                    <h2 class="text-sm font-semibold text-muted-foreground">Glossary</h2>
                    <dl class="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:divide-sidebar-border dark:border-sidebar-border">
                        <div v-for="(item, index) in glossaryTerms" :key="index" class="py-2 first:pt-0 last:pb-0">
                            <dt class="text-sm font-medium">{{ item.term }}</dt>
                            <dd class="mt-0.5 text-sm text-muted-foreground">{{ item.definition }}</dd>
                        </div>
                    </dl>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
