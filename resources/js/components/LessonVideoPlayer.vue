<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Loader2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

interface Props {
    lessonId: number;
    class?: string;
}

const props = defineProps<Props>();

const playbackUrl = ref<string | null>(null);
const loading = ref(false);
const error = ref(false);

async function load(): Promise<void> {
    loading.value = true;
    error.value = false;

    try {
        const response = await fetch(route('lessons.video.playback-token', props.lessonId));

        if (!response.ok) {
            throw new Error('Failed to fetch video playback token');
        }

        const data = await response.json();
        playbackUrl.value = data.url;
    } catch {
        error.value = true;
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

<template>
    <div :class="props.class">
        <div v-if="loading" class="flex aspect-video items-center justify-center rounded-lg bg-muted">
            <Loader2 class="size-6 animate-spin text-muted-foreground" />
        </div>
        <div v-else-if="error" class="flex aspect-video flex-col items-center justify-center gap-3 rounded-lg bg-muted">
            <p class="text-xs text-muted-foreground">Couldn't load the video.</p>
            <Button size="sm" variant="outline" @click="load">Try Again</Button>
        </div>
        <iframe
            v-else-if="playbackUrl"
            :src="playbackUrl"
            class="aspect-video w-full rounded-lg"
            allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
            allowfullscreen
        ></iframe>
    </div>
</template>
