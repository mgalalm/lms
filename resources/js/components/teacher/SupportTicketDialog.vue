<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    classSessionId: number;
}>();

const open = defineModel<boolean>('open', { required: true });

const SUBJECTS = ['Frozen Video', 'Student No Show', 'Zoom Link Not Working', 'Other'];

const form = useForm({
    subject: '',
    description: '',
});

function submit(): void {
    form.post(route('teaching.support-tickets.store', props.classSessionId), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            open.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Report an Issue</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="subject">Subject</Label>
                    <select id="subject" v-model="form.subject" class="rounded-md border border-input bg-background px-3 py-2 text-sm">
                        <option value="" disabled>Select an issue type</option>
                        <option v-for="subject in SUBJECTS" :key="subject" :value="subject">{{ subject }}</option>
                    </select>
                    <p v-if="form.errors.subject" class="text-sm text-destructive">{{ form.errors.subject }}</p>
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        placeholder="What happened?"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">Open Ticket</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
