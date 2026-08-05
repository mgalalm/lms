<script setup lang="ts">
import NextSessionCard from '@/components/NextSessionCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, ClassSession } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    nextSession: ClassSession | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <NextSessionCard :session="nextSession" viewer-role="student" />
        </div>
    </AppLayout>
</template>
