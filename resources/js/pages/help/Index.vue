<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, HelpArticleDetail, HelpArticleSummary } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { LifeBuoy, Search } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    categories: Record<string, HelpArticleSummary[]>;
    selected: HelpArticleDetail | null;
    search: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Help', href: route('help.index') }];

const search = ref(props.search);

function applySearch(): void {
    router.get(props.selected ? route('help.show', props.selected.slug) : route('help.index'), { search: search.value }, { preserveState: true });
}

// The native search input's clear (X) button — and just backspacing to
// empty — doesn't fire keyup.enter, so the stale filtered list would
// otherwise stick around until the user explicitly searches again.
watch(search, (value, previous) => {
    if (value === '' && previous !== '') {
        applySearch();
    }
});

function articleHref(slug: string): string {
    return route('help.show', { article: slug, search: search.value || undefined });
}
</script>

<template>
    <Head title="Help" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <div class="flex w-80 shrink-0 flex-col border-r border-sidebar-border/70 dark:border-sidebar-border">
                <div class="border-b border-sidebar-border/70 p-3 dark:border-sidebar-border">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search help topics"
                            class="fi-input w-full rounded-md border border-input bg-background py-1.5 pl-8 pr-3 text-sm"
                            @keyup.enter="applySearch"
                        />
                    </div>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto p-3">
                    <p v-if="Object.keys(categories).length === 0" class="text-sm text-muted-foreground">No topics match your search.</p>

                    <div v-for="(articles, category) in categories" :key="category" class="space-y-1">
                        <h3 class="px-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ category }}</h3>
                        <Link
                            v-for="article in articles"
                            :key="article.slug"
                            :href="articleHref(article.slug)"
                            preserve-state
                            class="block rounded-md px-2 py-1.5 text-sm hover:bg-accent"
                            :class="selected?.slug === article.slug ? 'bg-accent font-medium' : ''"
                        >
                            {{ article.title }}
                            <p v-if="article.excerpt" class="text-xs text-muted-foreground">{{ article.excerpt }}</p>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6">
                <template v-if="selected">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ selected.category }}</p>
                    <h1 class="mb-4 mt-1 text-2xl font-bold">{{ selected.title }}</h1>
                    <div class="help-content max-w-2xl text-sm leading-relaxed" v-html="selected.body_html" />
                </template>

                <div v-else class="flex h-full flex-col items-center justify-center gap-2 text-center text-muted-foreground">
                    <LifeBuoy class="size-10" />
                    <p class="text-sm">Pick a topic on the left to get started.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.help-content :deep(h1),
.help-content :deep(h2),
.help-content :deep(h3) {
    margin-top: 1.25em;
    margin-bottom: 0.5em;
    font-weight: 600;
}

.help-content :deep(p) {
    margin-bottom: 0.75em;
}

.help-content :deep(ul),
.help-content :deep(ol) {
    margin-bottom: 0.75em;
    padding-left: 1.5em;
}

.help-content :deep(ul) {
    list-style-type: disc;
}

.help-content :deep(ol) {
    list-style-type: decimal;
}

.help-content :deep(strong) {
    font-weight: 600;
}

.help-content :deep(code) {
    border-radius: 0.25em;
    background-color: hsl(var(--muted));
    padding: 0.15em 0.4em;
    font-size: 0.85em;
}
</style>
