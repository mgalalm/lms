<script setup lang="ts">
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const page = usePage<SharedData>();
const visible = ref(false);
const message = ref('');
const variant = ref<'success' | 'error'>('success');
let timeoutId: ReturnType<typeof setTimeout> | null = null;

function show(value: string, kind: 'success' | 'error'): void {
    message.value = value;
    variant.value = kind;
    visible.value = true;

    if (timeoutId) {
        clearTimeout(timeoutId);
    }

    timeoutId = setTimeout(() => {
        visible.value = false;
    }, 4000);
}

watch(
    () => page.props.flash.success,
    (value) => value && show(value, 'success'),
    { immediate: true },
);

watch(
    () => page.props.flash.error,
    (value) => value && show(value, 'error'),
    { immediate: true },
);
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible && variant === 'success'"
            class="fixed bottom-4 right-4 z-50 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-lg dark:border-green-900 dark:bg-green-950 dark:text-green-300"
        >
            <CheckCircle2 class="size-4" />
            {{ message }}
        </div>
        <div
            v-else-if="visible && variant === 'error'"
            class="fixed bottom-4 right-4 z-50 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-lg dark:border-red-900 dark:bg-red-950 dark:text-red-300"
        >
            <AlertCircle class="size-4" />
            {{ message }}
        </div>
    </Transition>
</template>
