<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { BookOpen } from 'lucide-vue-next';

const props = defineProps<{
    lesson: { id: number; title: string };
    introHtml: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'LMS', href: route('lms.index') },
    { title: props.lesson.title, href: '#' },
    { title: 'Tutor Guide', href: '#' },
];
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
                v-if="!introHtml"
                :icon="BookOpen"
                heading="No tutor guide yet"
                description="Link and sync a Notion tutor guide for this lesson from the admin panel."
            />

            <div v-else class="space-y-3">
                <h2 class="text-muted-foreground text-sm font-semibold">Tutor Notes</h2>
                <div
                    class="tutor-guide-content border-sidebar-border/70 bg-card dark:border-sidebar-border max-w-none rounded-xl border p-4 text-sm leading-relaxed break-words"
                    v-html="introHtml"
                />
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.tutor-guide-content {
    overflow-x: auto;
}

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

.tutor-guide-content :deep(input[type='checkbox']) {
    margin-right: 0.45em;
    height: 1em;
    width: 1em;
    vertical-align: -0.15em;
    accent-color: hsl(var(--primary));
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

.tutor-guide-content :deep(table) {
    /* No fixed width: Notion's public API doesn't expose per-column
       pixel widths, so the closest match to how a table actually looked
       in Notion is to let column widths size to their own content
       (the browser's default table-layout behavior) rather than forcing
       every table — including small ones — to stretch to fill the page. */
    margin-top: 1em;
    margin-bottom: 1em;
    border-collapse: collapse;
    font-size: 0.9em;
}

.tutor-guide-content :deep(th),
.tutor-guide-content :deep(td) {
    border: 1px solid hsl(var(--border));
    padding: 0.5em 0.75em;
    text-align: left;
}

.tutor-guide-content :deep(th) {
    background-color: hsl(var(--muted));
    font-weight: 600;
}

.tutor-guide-content :deep(iframe) {
    margin-top: 1em;
    margin-bottom: 1em;
    aspect-ratio: 16 / 9;
    width: 100%;
    max-width: 56rem;
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
    position: relative;
    margin-bottom: 0.85em;
    overflow-x: auto;
    border-radius: 0.5rem;
    border: 1px solid hsl(var(--border));
    background-color: #111827;
    padding: 1rem;
    color: #d1d5db;
}

.tutor-guide-content :deep(pre code) {
    background-color: transparent;
    padding: 0;
    color: inherit;
}

.tutor-guide-content :deep(pre[data-language]) {
    padding-top: 2.25rem;
}

.tutor-guide-content :deep(pre[data-language]::before) {
    position: absolute;
    top: 0.65rem;
    right: 0.85rem;
    color: #9ca3af;
    content: attr(data-language);
    font-size: 0.7rem;
    font-weight: 600;
}

.tutor-guide-content :deep(.hljs-keyword),
.tutor-guide-content :deep(.hljs-selector-tag),
.tutor-guide-content :deep(.hljs-built_in),
.tutor-guide-content :deep(.hljs-name),
.tutor-guide-content :deep(.hljs-tag) {
    color: #93c5fd;
}

.tutor-guide-content :deep(.hljs-string),
.tutor-guide-content :deep(.hljs-title),
.tutor-guide-content :deep(.hljs-section),
.tutor-guide-content :deep(.hljs-attribute),
.tutor-guide-content :deep(.hljs-literal),
.tutor-guide-content :deep(.hljs-template-tag),
.tutor-guide-content :deep(.hljs-template-variable),
.tutor-guide-content :deep(.hljs-type),
.tutor-guide-content :deep(.hljs-addition) {
    color: #86efac;
}

.tutor-guide-content :deep(.hljs-number),
.tutor-guide-content :deep(.hljs-symbol),
.tutor-guide-content :deep(.hljs-bullet),
.tutor-guide-content :deep(.hljs-variable),
.tutor-guide-content :deep(.hljs-regexp),
.tutor-guide-content :deep(.hljs-link) {
    color: #fca5a5;
}

.tutor-guide-content :deep(.hljs-comment),
.tutor-guide-content :deep(.hljs-quote),
.tutor-guide-content :deep(.hljs-deletion),
.tutor-guide-content :deep(.hljs-meta) {
    color: #9ca3af;
}

.tutor-guide-content :deep(.hljs-emphasis) {
    font-style: italic;
}

.tutor-guide-content :deep(.hljs-strong) {
    font-weight: 600;
}
</style>
