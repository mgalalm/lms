<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { ClassSession } from '@/types';
import { router } from '@inertiajs/vue3';
import { Frown, Meh, Smile, Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    session: ClassSession;
}>();

const open = ref(true);
const rating = ref(3);
const submitting = ref(false);

const emoji = computed(() => {
    if (rating.value <= 2) {
        return Frown;
    }

    if (rating.value === 3) {
        return Meh;
    }

    return Smile;
});

function lessonPath(session: ClassSession): string {
    return [session.lesson.module?.course?.title, session.lesson.module?.title, session.lesson.title].filter(Boolean).join(' / ');
}

function submit(): void {
    submitting.value = true;

    router.post(
        route('sessions.feedback.store', props.session.id),
        { star_rating: rating.value },
        {
            preserveScroll: true,
            onFinish: () => {
                submitting.value = false;
                open.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>What is your Feedback for the session?</DialogTitle>
                <DialogDescription>{{ lessonPath(session) }} with {{ session.teacher?.name }}</DialogDescription>
            </DialogHeader>

            <div class="flex flex-col items-center gap-4 py-4">
                <component :is="emoji" class="size-12 text-primary" />

                <div class="flex gap-1">
                    <button
                        v-for="value in [1, 2, 3, 4, 5]"
                        :key="value"
                        type="button"
                        class="p-1"
                        :aria-label="`${value} stars`"
                        @click="rating = value"
                    >
                        <Star class="size-7" :class="value <= rating ? 'fill-primary text-primary' : 'text-muted-foreground'" />
                    </button>
                </div>
            </div>

            <DialogFooter>
                <Button :disabled="submitting" @click="submit">Submit</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
