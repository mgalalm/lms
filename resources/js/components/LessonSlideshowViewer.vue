<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Maximize, Minimize } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

interface Props {
    googleSlidesId: string;
    class?: string;
}

const props = defineProps<Props>();

const viewerEl = ref<HTMLDivElement | null>(null);
const isFullscreen = ref(false);

async function toggleFullscreen(): Promise<void> {
    if (!document.fullscreenElement) {
        await viewerEl.value?.requestFullscreen();
    } else {
        await document.exitFullscreen();
    }
}

function onFullscreenChange(): void {
    isFullscreen.value = document.fullscreenElement === viewerEl.value;
}

onMounted(() => {
    document.addEventListener('fullscreenchange', onFullscreenChange);
});

onUnmounted(() => {
    document.removeEventListener('fullscreenchange', onFullscreenChange);
});
</script>

<template>
    <div :class="props.class">
        <div
            ref="viewerEl"
            class="space-y-3"
            :class="isFullscreen ? 'flex h-full w-full flex-col items-center justify-center bg-black p-4' : ''"
        >
            <div class="relative aspect-video w-full overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
                <iframe
                    :src="`https://docs.google.com/presentation/d/${googleSlidesId}/embed`"
                    class="h-full w-full"
                    allowfullscreen
                ></iframe>
                <!--
                    Covers Google's own watermark in the embed's bottom-right
                    corner — there's no render mode that keeps the nav
                    arrows without it (rm=minimal drops both together), and
                    since the iframe is cross-origin we can't inspect its
                    actual layout to size this precisely. pointer-events-none
                    so it never blocks a real click underneath (e.g. if the
                    watermark itself is clickable) — this is a best-effort
                    visual cover, not a guarantee, and may need retuning if
                    it doesn't line up in practice.
                -->
                <div class="pointer-events-none absolute right-0 bottom-0 h-9 w-44 bg-white"></div>
            </div>

            <div class="flex items-center justify-center">
                <Button size="sm" variant="outline" @click="toggleFullscreen">
                    <Minimize v-if="isFullscreen" class="size-4" />
                    <Maximize v-else class="size-4" />
                    {{ isFullscreen ? 'Exit Full Screen' : 'Full Screen' }}
                </Button>
            </div>
        </div>
    </div>
</template>
