<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, PayrollEntry } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Copy } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    entries: PayrollEntry[];
    bank: { name: string | null; account_number: string | null };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Payroll', href: route('payroll.index') }];

const detailsEntry = ref<PayrollEntry | null>(null);

function maskedAccountNumber(value: string | null): string {
    if (!value) {
        return '—';
    }

    return `•••• ${value.slice(-4)}`;
}

function copyAccountNumber(): void {
    if (props.bank.account_number) {
        navigator.clipboard.writeText(props.bank.account_number);
    }
}

const statusPillClasses: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    paid: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
};
</script>

<template>
    <Head title="Payroll" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between gap-4 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                <div>
                    <p class="text-xs text-muted-foreground">Bank</p>
                    <p class="font-medium">{{ bank.name ?? '—' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="text-right">
                        <p class="text-xs text-muted-foreground">Account Number</p>
                        <p class="font-mono">{{ maskedAccountNumber(bank.account_number) }}</p>
                    </div>
                    <Button variant="outline" size="sm" :disabled="!bank.account_number" @click="copyAccountNumber">
                        <Copy class="size-3.5" />
                        Copy
                    </Button>
                </div>
            </div>

            <p v-if="entries.length === 0" class="text-sm text-muted-foreground">No payroll entries yet.</p>

            <div v-else class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-xs text-muted-foreground">
                        <tr>
                            <th class="p-3 font-medium">Month</th>
                            <th class="p-3 font-medium">Organization</th>
                            <th class="p-3 font-medium">Count</th>
                            <th class="p-3 font-medium">Basic Salary</th>
                            <th class="p-3 font-medium">Bonus/Deduction</th>
                            <th class="p-3 font-medium">Net Amount</th>
                            <th class="p-3 font-medium">Status</th>
                            <th class="p-3 font-medium">Completed at</th>
                            <th class="p-3 font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                        <tr v-for="entry in entries" :key="entry.id">
                            <td class="p-3 font-medium">{{ entry.month }}</td>
                            <td class="p-3">{{ entry.organization }}</td>
                            <td class="p-3">{{ entry.sessions_count }}</td>
                            <td class="p-3">{{ entry.basic_salary.toLocaleString() }} EGP</td>
                            <td class="p-3">
                                <button
                                    v-if="entry.adjustments.length > 0"
                                    type="button"
                                    class="text-primary hover:underline"
                                    @click="detailsEntry = entry"
                                >
                                    View Details
                                </button>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="p-3 font-medium">{{ entry.net_amount.toLocaleString() }} EGP</td>
                            <td class="p-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusPillClasses[entry.status]">
                                    {{ entry.status }}
                                </span>
                            </td>
                            <td class="p-3 text-muted-foreground">
                                {{ entry.completed_at ? new Date(entry.completed_at).toLocaleDateString() : '-' }}
                            </td>
                            <td class="p-3">
                                <Link :href="route('teaching.sessions-schedule', { filter: 'past' })" class="text-primary hover:underline">
                                    Session History
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Dialog :open="detailsEntry !== null" @update:open="(value) => !value && (detailsEntry = null)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ detailsEntry?.month }} Adjustments</DialogTitle>
                </DialogHeader>

                <div class="space-y-2">
                    <div
                        v-for="(adjustment, index) in detailsEntry?.adjustments ?? []"
                        :key="index"
                        class="flex items-center justify-between text-sm"
                    >
                        <div>
                            <span
                                class="mr-2 rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="
                                    adjustment.type === 'bonus'
                                        ? 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300'
                                        : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'
                                "
                            >
                                {{ adjustment.type }}
                            </span>
                            {{ adjustment.reason }}
                        </div>
                        <span class="font-medium">{{ adjustment.amount.toLocaleString() }} EGP</span>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
