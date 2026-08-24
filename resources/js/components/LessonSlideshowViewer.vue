<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight, Loader2 } from 'lucide-vue-next';
import * as pdfjsLib from 'pdfjs-dist';
import type { PDFDocumentProxy } from 'pdfjs-dist';
import pdfjsWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
import { onMounted, onUnmounted, ref, watch } from 'vue';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorkerUrl;

interface Props {
    lessonId: number;
    class?: string;
}

const props = defineProps<Props>();

const loading = ref(true);
const error = ref(false);
const currentPage = ref(1);
const totalPages = ref(0);
const canvasEl = ref<HTMLCanvasElement | null>(null);
const thumbnails = ref<string[]>([]);

let pdfDocument: PDFDocumentProxy | null = null;

async function load(): Promise<void> {
    loading.value = true;
    error.value = false;

    try {
        const response = await fetch(route('lessons.slideshow.playback-token', props.lessonId));

        if (!response.ok) {
            throw new Error('Failed to fetch slideshow playback token');
        }

        const data = await response.json();

        pdfDocument?.destroy();
        pdfDocument = await pdfjsLib.getDocument({ url: data.url }).promise;
        totalPages.value = pdfDocument.numPages;
        currentPage.value = 1;

        await renderPage(1);
        renderThumbnails();
    } catch {
        error.value = true;
    } finally {
        loading.value = false;
    }
}

async function renderPage(pageNumber: number): Promise<void> {
    if (!pdfDocument || !canvasEl.value) {
        return;
    }

    const page = await pdfDocument.getPage(pageNumber);
    const viewport = page.getViewport({ scale: 1.5 });
    const canvas = canvasEl.value;

    canvas.width = viewport.width;
    canvas.height = viewport.height;

    await page.render({ canvas, viewport }).promise;
}

async function renderThumbnails(): Promise<void> {
    if (!pdfDocument) {
        return;
    }

    const results: string[] = [];

    for (let pageNumber = 1; pageNumber <= pdfDocument.numPages; pageNumber++) {
        const page = await pdfDocument.getPage(pageNumber);
        const viewport = page.getViewport({ scale: 0.2 });
        const canvas = document.createElement('canvas');
        canvas.width = viewport.width;
        canvas.height = viewport.height;

        await page.render({ canvas, viewport }).promise;
        results.push(canvas.toDataURL());
    }

    thumbnails.value = results;
}

function goToPage(page: number): void {
    if (page < 1 || page > totalPages.value) {
        return;
    }

    currentPage.value = page;
}

watch(currentPage, (page) => {
    renderPage(page);
});

onMounted(load);

onUnmounted(() => {
    pdfDocument?.destroy();
    pdfDocument = null;
});
</script>

<template>
    <div :class="props.class">
        <div v-if="loading" class="flex aspect-video items-center justify-center rounded-lg bg-muted">
            <Loader2 class="size-6 animate-spin text-muted-foreground" />
        </div>
        <div v-else-if="error" class="flex aspect-video flex-col items-center justify-center gap-3 rounded-lg bg-muted">
            <p class="text-xs text-muted-foreground">Couldn't load the slideshow.</p>
            <Button size="sm" variant="outline" @click="load">Try Again</Button>
        </div>
        <div v-else class="space-y-3">
            <div
                class="flex items-center justify-center overflow-auto rounded-lg border border-sidebar-border/70 bg-muted p-2 dark:border-sidebar-border"
            >
                <canvas ref="canvasEl" class="max-w-full" />
            </div>

            <div class="flex items-center justify-center gap-3">
                <Button size="sm" variant="outline" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">
                    <ChevronLeft class="size-4" />
                    Previous
                </Button>
                <span class="text-sm text-muted-foreground">Slide {{ currentPage }} of {{ totalPages }}</span>
                <Button size="sm" variant="outline" :disabled="currentPage >= totalPages" @click="goToPage(currentPage + 1)">
                    Next
                    <ChevronRight class="size-4" />
                </Button>
            </div>

            <div v-if="thumbnails.length > 1" class="flex gap-2 overflow-x-auto pb-1">
                <button
                    v-for="(thumbnail, index) in thumbnails"
                    :key="index"
                    type="button"
                    class="shrink-0 overflow-hidden rounded border-2 transition-colors"
                    :class="currentPage === index + 1 ? 'border-primary' : 'border-transparent'"
                    @click="goToPage(index + 1)"
                >
                    <img :src="thumbnail" :alt="`Slide ${index + 1}`" class="h-16 w-auto" />
                </button>
            </div>
        </div>
    </div>
</template>
