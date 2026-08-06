<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import RequestFormModal from '@/components/requests/RequestFormModal.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, LeaveCreditBalance, Paginated, TeacherRequestHistoryItem, TeacherRequestType } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarClock, FileText, LifeBuoy, LogOut, UserCog } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    type: TeacherRequestType;
    history: Paginated<TeacherRequestHistoryItem>;
    leaveCredit: LeaveCreditBalance;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Requests', href: route('requests.index') }];

const requestTypes: { key: TeacherRequestType; label: string; description: string; icon: typeof CalendarClock }[] = [
    { key: 'leave', label: 'Leave Request', description: 'Planned or emergency leave request.', icon: CalendarClock },
    { key: 'excused_time', label: 'Excused Time', description: 'Absence for valid reasons.', icon: FileText },
    { key: 'hr_request', label: 'HR Request', description: 'Raise and manage HR tickets.', icon: UserCog },
    { key: 'support', label: 'Support', description: 'Submit a ticket, inquiry, or recommendation.', icon: LifeBuoy },
    { key: 'resignation', label: 'Resignation', description: 'Stepping down from position.', icon: LogOut },
];

const modalOpen = ref(false);
const modalType = ref<TeacherRequestType>('leave');

function openModal(type: TeacherRequestType): void {
    modalType.value = type;
    modalOpen.value = true;
}

const statusPillClasses: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    approved: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
};

function historyLabel(item: TeacherRequestHistoryItem): string {
    if (item.payload.sub_reason) {
        return item.payload.sub_reason;
    }

    return requestTypes.find((option) => option.key === item.type)?.label ?? item.type;
}

function historyDate(item: TeacherRequestHistoryItem): string {
    if (!item.payload.start_date) {
        return '';
    }

    return item.payload.end_date && item.payload.end_date !== item.payload.start_date
        ? `${item.payload.start_date} – ${item.payload.end_date}`
        : item.payload.start_date;
}
</script>

<template>
    <Head title="Requests" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <RequestFormModal v-model:open="modalOpen" :type="modalType" :leave-credit="leaveCredit" />

        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <button
                    v-for="option in requestTypes"
                    :key="option.key"
                    type="button"
                    class="flex items-start gap-3 rounded-xl border border-sidebar-border/70 bg-card p-4 text-left transition-colors hover:border-sidebar-border dark:border-sidebar-border"
                    @click="openModal(option.key)"
                >
                    <component :is="option.icon" class="size-6 shrink-0 text-primary" />
                    <div>
                        <p class="font-medium">{{ option.label }}</p>
                        <p class="text-xs text-muted-foreground">{{ option.description }}</p>
                    </div>
                </button>
            </div>

            <div class="space-y-3">
                <div class="flex flex-wrap gap-2">
                    <Link v-for="option in requestTypes" :key="option.key" :href="route('requests.index', { type: option.key })" preserve-state>
                        <Button :variant="type === option.key ? 'default' : 'outline'" size="sm">{{ option.label }}</Button>
                    </Link>
                </div>

                <p v-if="history.data.length === 0" class="text-sm text-muted-foreground">No requests submitted yet.</p>

                <div
                    v-else
                    class="divide-y divide-sidebar-border/70 rounded-xl border border-sidebar-border/70 bg-card dark:divide-sidebar-border dark:border-sidebar-border"
                >
                    <div v-for="item in history.data" :key="item.id" class="flex items-center justify-between gap-4 p-4">
                        <div>
                            <p class="font-medium">
                                {{ historyLabel(item) }}
                                <span v-if="historyDate(item)" class="font-normal text-muted-foreground">({{ historyDate(item) }})</span>
                            </p>
                            <p class="text-xs text-muted-foreground">Submitted {{ new Date(item.submitted_at).toLocaleDateString() }}</p>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusPillClasses[item.status]">
                            {{ item.status }}
                        </span>
                    </div>
                </div>

                <Pagination :links="history.meta.links" />
            </div>
        </div>
    </AppLayout>
</template>
