<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    links: PaginationLink[];
}>();

// Laravel's paginator labels contain HTML entities (e.g. "&laquo; Previous"), so
// decode them to plain text rather than reaching for v-html on a component.
function decodeLabel(label: string): string {
    const el = document.createElement('div');
    el.innerHTML = label;

    return el.textContent ?? '';
}
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap items-center justify-center gap-1">
        <template v-for="(link, index) in links" :key="index">
            <span v-if="!link.url" class="rounded-md px-3 py-1.5 text-sm text-muted-foreground">{{ decodeLabel(link.label) }}</span>
            <Link
                v-else
                :href="link.url"
                preserve-scroll
                class="rounded-md px-3 py-1.5 text-sm hover:bg-accent"
                :class="link.active ? 'bg-primary text-primary-foreground hover:bg-primary' : 'text-foreground'"
            >
                {{ decodeLabel(link.label) }}
            </Link>
        </template>
    </nav>
</template>
