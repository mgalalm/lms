<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

defineProps<{
    icon: LucideIcon;
    heading: string;
    description: string;
    ctaLabel?: string;
    ctaHref?: string;
}>();
</script>

<template>
    <div class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-6 text-center dark:border-sidebar-border">
        <div class="flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground">
            <component :is="icon" class="size-5" />
        </div>
        <div class="space-y-1">
            <p class="text-sm font-medium">{{ heading }}</p>
            <p class="text-xs text-muted-foreground">{{ description }}</p>
        </div>
        <Button v-if="ctaLabel && ctaHref" as-child size="sm">
            <Link :href="ctaHref">{{ ctaLabel }}</Link>
        </Button>
    </div>
</template>
