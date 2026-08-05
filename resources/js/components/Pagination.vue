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
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap items-center justify-center gap-1">
        <template v-for="(link, index) in links" :key="index">
            <span v-if="!link.url" class="rounded-md px-3 py-1.5 text-sm text-muted-foreground" v-html="link.label" />
            <Link
                v-else
                :href="link.url"
                preserve-scroll
                class="rounded-md px-3 py-1.5 text-sm hover:bg-accent"
                :class="link.active ? 'bg-primary text-primary-foreground hover:bg-primary' : 'text-foreground'"
                v-html="link.label"
            />
        </template>
    </nav>
</template>
