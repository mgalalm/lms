<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import wordmark from '@/assets/branding/gen-academy-primary.svg';
import wordmarkDark from '@/assets/branding/gen-academy-primary-dark.svg';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';

interface Props {
    class?: string;
}

const props = defineProps<Props>();

const page = usePage<SharedData>();
</script>

<template>
    <div class="flex items-center" :class="props.class">
        <AppLogoIcon class="hidden size-8 shrink-0 group-data-[collapsible=icon]:block" />
        <div class="flex flex-1 items-center overflow-hidden group-data-[collapsible=icon]:hidden">
            <img :src="wordmark" :alt="page.props.name" class="h-6 w-auto max-w-none dark:hidden" />
            <img :src="wordmarkDark" :alt="page.props.name" class="hidden h-6 w-auto max-w-none dark:block" />
        </div>
    </div>
</template>
