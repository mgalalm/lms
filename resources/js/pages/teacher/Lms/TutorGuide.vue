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
                <p class="text-muted-foreground text-sm">{{ lesson.title }} — staff-only prep material, never shown to students.</p>
            </div>

            <EmptyState
                v-if="!introHtml && procedureSteps.length === 0 && glossaryTerms.length === 0"
                :icon="BookOpen"
                heading="No tutor guide yet"
                description="Add an intro, procedure steps, and glossary terms for this lesson from the admin panel."
            />

            <template v-else>
                <div v-if="introHtml" class="space-y-3">
                    <h2 class="text-muted-foreground text-sm font-semibold">Intro</h2>
                    <div
                        class="tutor-guide-content border-sidebar-border/70 bg-card dark:border-sidebar-border max-w-none rounded-xl border p-4 text-sm leading-relaxed break-words"
                        v-html="introHtml"
                    />
                </div>

                <div v-if="procedureSteps.length > 0" class="space-y-3">
                    <h2 class="text-muted-foreground text-sm font-semibold">Procedure Steps</h2>
                    <ol
                        class="divide-sidebar-border/70 border-sidebar-border/70 bg-card dark:divide-sidebar-border dark:border-sidebar-border divide-y rounded-xl border"
                    >
                        <li v-for="(step, index) in procedureSteps" :key="index" class="flex items-center justify-between gap-4 p-4">
                            <div class="flex items-center gap-3">
                                <span class="text-muted-foreground text-sm font-medium">{{ index + 1 }}.</span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="phaseStyles[step.phase]">
                                    {{ phaseLabels[step.phase] }}
                                </span>
                                <p class="text-sm font-medium">{{ step.title }}</p>
                            </div>
                            <span class="bg-muted text-muted-foreground shrink-0 rounded-full px-2 py-0.5 text-xs">
                                {{ step.duration_minutes }} min
                            </span>
                        </li>
                    </ol>
                </div>

                <div v-if="glossaryTerms.length > 0" class="space-y-3">
                    <h2 class="text-muted-foreground text-sm font-semibold">Glossary</h2>
                    <dl
                        class="divide-sidebar-border/70 border-sidebar-border/70 bg-card dark:divide-sidebar-border dark:border-sidebar-border divide-y rounded-xl border p-4"
                    >
                        <div v-for="(item, index) in glossaryTerms" :key="index" class="py-2 first:pt-0 last:pb-0">
                            <dt class="text-sm font-medium">{{ item.term }}</dt>
                            <dd class="text-muted-foreground mt-0.5 text-sm">{{ item.definition }}</dd>
                        </div>
                    </dl>
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<style scoped>
.tutor-guide-content :deep(h1),
.tutor-guide-content :deep(h2),
.tutor-guide-content :deep(h3) {
    margin-top: 1.25em;
    margin-bottom: 0.5em;
    font-weight: 600;
    line-height: 1.25;
}

.tutor-guide-content :deep(h1) {
    font-size: 1.5em;
}

.tutor-guide-content :deep(h2) {
    font-size: 1.25em;
}

.tutor-guide-content :deep(h3) {
    font-size: 1.1em;
}

.tutor-guide-content :deep(p) {
    margin-bottom: 0.85em;
}

.tutor-guide-content :deep(ul),
.tutor-guide-content :deep(ol) {
    margin-bottom: 0.85em;
    padding-left: 1.5em;
}

.tutor-guide-content :deep(ul) {
    list-style-type: disc;
}

.tutor-guide-content :deep(ol) {
    list-style-type: decimal;
}

.tutor-guide-content :deep(li) {
    margin-bottom: 0.35em;
}

.tutor-guide-content :deep(a) {
    color: hsl(var(--primary));
    text-decoration: underline;
    text-underline-offset: 0.15em;
}

.tutor-guide-content :deep(strong) {
    font-weight: 600;
}

.tutor-guide-content :deep(blockquote) {
    margin-bottom: 0.85em;
    border-left: 2px solid hsl(var(--border));
    padding-left: 0.85em;
    color: hsl(var(--muted-foreground));
    font-style: italic;
}

.tutor-guide-content :deep(img) {
    margin-top: 1em;
    margin-bottom: 1em;
    max-width: 100%;
    border-radius: 0.5rem;
    border: 1px solid hsl(var(--border));
}

.tutor-guide-content :deep(code) {
    border-radius: 0.25em;
    background-color: hsl(var(--muted));
    padding: 0.15em 0.4em;
    font-size: 0.85em;
}

.tutor-guide-content :deep(pre) {
    margin-bottom: 0.85em;
    overflow-x: auto;
    border-radius: 0.5rem;
    background-color: hsl(var(--muted));
    padding: 0.85em;
}

.tutor-guide-content :deep(pre code) {
    background-color: transparent;
    padding: 0;
}
</style>
