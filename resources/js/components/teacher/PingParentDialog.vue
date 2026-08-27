<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    classSessionId: number;
    studentName: string;
}>();

const open = defineModel<boolean>('open', { required: true });

const REASONS: Record<string, string> = {
    late_join: 'Late Join',
    session_issue: 'Session Issue',
};

const TEMPLATES: Record<string, (studentName: string) => string> = {
    late_join: (studentName) =>
        `Hi! Just a quick note — ${studentName} joined today's session a few minutes late, which cut into lesson time. It would help a lot if they could log in a few minutes early next time so we can start on time. Thanks for your help with this!`,
    session_issue: (studentName) =>
        `Hi! We ran into a technical issue during ${studentName}'s session today that affected part of the lesson. Wanted to give you a heads up — if this keeps happening, please let us know so we can help troubleshoot.`,
};

const form = useForm({
    reason: '',
    message: '',
});

function applyTemplate(): void {
    if (form.reason in TEMPLATES) {
        form.message = TEMPLATES[form.reason](props.studentName);
    }
}

function submit(): void {
    form.post(route('teaching.sessions.ping-parent', props.classSessionId), {
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
                <DialogTitle>Ping Parent</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="ping-reason">Reason</Label>
                    <select
                        id="ping-reason"
                        v-model="form.reason"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                        @change="applyTemplate"
                    >
                        <option value="" disabled>Select a reason</option>
                        <option v-for="(label, value) in REASONS" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>

                <div class="grid gap-2">
                    <Label for="ping-message">Message</Label>
                    <textarea
                        id="ping-message"
                        v-model="form.message"
                        rows="6"
                        placeholder="What would you like to tell the parent?"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p v-if="form.errors.message" class="text-sm text-destructive">{{ form.errors.message }}</p>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">Send</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
