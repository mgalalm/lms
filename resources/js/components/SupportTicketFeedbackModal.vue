<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { SupportTicket } from '@/types';
import { router } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    ticket: SupportTicket;
}>();

const open = ref(true);
const rating = ref(3);
const comment = ref('');
const submitting = ref(false);

function submit(): void {
    submitting.value = true;

    router.post(
        route('teaching.support-tickets.feedback', props.ticket.id),
        { rating: rating.value, comment: comment.value || undefined },
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
                <DialogTitle>Rate Support #{{ ticket.code }}</DialogTitle>
                <DialogDescription>Topic: {{ ticket.subject }}</DialogDescription>
            </DialogHeader>

            <div class="flex flex-col items-center gap-4 py-4">
                <p class="text-sm text-muted-foreground">How satisfied are you with how this ticket was resolved?</p>

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

                <div class="w-full space-y-1.5">
                    <label for="ticket-feedback-comment" class="text-xs text-muted-foreground">Comment (optional)</label>
                    <textarea
                        id="ticket-feedback-comment"
                        v-model="comment"
                        rows="3"
                        placeholder="Add your comment here…"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>
            </div>

            <DialogFooter>
                <Button class="w-full" :disabled="submitting" @click="submit">Submit</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
