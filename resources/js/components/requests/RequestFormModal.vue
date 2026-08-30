<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { LeaveCreditBalance, TeacherRequestType } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps<{
    type: TeacherRequestType;
    leaveCredit: LeaveCreditBalance;
}>();

const open = defineModel<boolean>('open', { required: true });

const typeLabels: Record<TeacherRequestType, string> = {
    leave: 'Leave Request',
    excused_time: 'Excused Time',
    hr_request: 'HR Request',
    support: 'General Inquiry',
    resignation: 'Resignation',
};

const form = useForm<{
    type: TeacherRequestType;
    leave_type: 'planned' | 'emergency' | 'sick';
    sub_reason: string;
    start_date: string;
    end_date: string;
    notes: string;
    attachment: File | null;
}>({
    type: props.type,
    leave_type: 'planned',
    sub_reason: '',
    start_date: '',
    end_date: '',
    notes: '',
    attachment: null,
});

watch(
    () => props.type,
    (value) => {
        form.reset();
        form.type = value;
    },
);

function onFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.attachment = target.files?.[0] ?? null;
}

function submit(): void {
    form.post(route('requests.store'), {
        forceFormData: true,
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
        <DialogContent class="max-h-[85vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{{ typeLabels[type] }}</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <template v-if="type === 'leave'">
                    <div class="grid gap-2">
                        <Label>Leave type</Label>
                        <div class="flex gap-4 text-sm">
                            <label v-for="option in ['planned', 'emergency', 'sick']" :key="option" class="flex items-center gap-1.5">
                                <input v-model="form.leave_type" type="radio" :value="option" />
                                {{ option === 'planned' ? 'Planned Leaves' : option.charAt(0).toUpperCase() + option.slice(1) }}
                            </label>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="sub_reason">Type</Label>
                        <Input id="sub_reason" v-model="form.sub_reason" placeholder="e.g. Vacation" />
                        <p v-if="form.errors.sub_reason" class="text-sm text-destructive">{{ form.errors.sub_reason }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="start_date">Start Date</Label>
                            <Input id="start_date" v-model="form.start_date" type="date" />
                            <p v-if="form.errors.start_date" class="text-sm text-destructive">{{ form.errors.start_date }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="end_date">End Date</Label>
                            <Input id="end_date" v-model="form.end_date" type="date" />
                            <p v-if="form.errors.end_date" class="text-sm text-destructive">{{ form.errors.end_date }}</p>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="notes">Notes</Label>
                        <textarea id="notes" v-model="form.notes" rows="3" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="attachment">Attachment</Label>
                        <input id="attachment" type="file" class="text-sm" @change="onFileChange" />
                    </div>

                    <div class="rounded-lg border border-sidebar-border/70 bg-muted/30 p-3 text-sm dark:border-sidebar-border">
                        <p class="font-medium">Credits</p>
                        <p class="text-muted-foreground">Monthly Limit: {{ leaveCredit.monthly_used }}/{{ leaveCredit.monthly_limit }} Days</p>
                        <p class="text-muted-foreground">Annual: {{ leaveCredit.annual_used }}/{{ leaveCredit.annual_limit }} Days</p>
                        <p class="mt-2 text-xs text-muted-foreground">Processing Time: 3 Working Days</p>
                    </div>
                </template>

                <template v-else-if="type === 'excused_time'">
                    <div class="grid gap-2">
                        <Label for="start_date">Date</Label>
                        <Input id="start_date" v-model="form.start_date" type="date" />
                        <p v-if="form.errors.start_date" class="text-sm text-destructive">{{ form.errors.start_date }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="notes">Notes</Label>
                        <textarea id="notes" v-model="form.notes" rows="3" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    </div>
                </template>

                <template v-else-if="type === 'hr_request' || type === 'support'">
                    <div class="grid gap-2">
                        <Label for="sub_reason">Subject</Label>
                        <Input id="sub_reason" v-model="form.sub_reason" />
                        <p v-if="form.errors.sub_reason" class="text-sm text-destructive">{{ form.errors.sub_reason }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="notes">Details</Label>
                        <textarea id="notes" v-model="form.notes" rows="4" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                        <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                    </div>
                </template>

                <template v-else-if="type === 'resignation'">
                    <div class="grid gap-2">
                        <Label for="start_date">Last Working Date</Label>
                        <Input id="start_date" v-model="form.start_date" type="date" />
                        <p v-if="form.errors.start_date" class="text-sm text-destructive">{{ form.errors.start_date }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="notes">Notes</Label>
                        <textarea id="notes" v-model="form.notes" rows="3" class="rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    </div>
                </template>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">Submit</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
