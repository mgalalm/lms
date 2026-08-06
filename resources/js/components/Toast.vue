<script setup lang="ts">
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const page = usePage<SharedData>();
const visible = ref(false);
const message = ref('');
let timeoutId: ReturnType<typeof setTimeout> | null = null;

watch(
    () => page.props.flash.success,
    (value) => {
        if (!value) {
            return;
        }

        message.value = value;
        visible.value = true;

        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        timeoutId = setTimeout(() => {
            visible.value = false;
        }, 4000);
    },
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
            v-if="visible"
            class="fixed bottom-4 right-4 z-50 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-lg dark:border-green-900 dark:bg-green-950 dark:text-green-300"
        >
            <CheckCircle2 class="size-4" />
            {{ message }}
        </div>
    </Transition>
</template>
