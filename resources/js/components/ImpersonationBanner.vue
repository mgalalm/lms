<script setup lang="ts">
import type { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { UserCircle } from 'lucide-vue-next';

const page = usePage<SharedData>();

function stop(): void {
    router.post(route('impersonate.stop'));
}
</script>

<template>
    <div
        v-if="page.props.impersonator"
        class="flex items-center justify-center gap-3 bg-amber-500 px-4 py-2 text-sm font-medium text-amber-950"
    >
        <UserCircle class="size-4" />
        <span>Logged in as {{ page.props.auth.user.name }} — impersonated by {{ page.props.impersonator.name }}</span>
        <button type="button" class="rounded-md bg-amber-950/10 px-2 py-0.5 hover:bg-amber-950/20" @click="stop">Return to Admin</button>
    </div>
</template>
