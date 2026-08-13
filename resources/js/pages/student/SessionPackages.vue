<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, SessionPackage, SessionPackagePurchase, SessionPackagePurchaseStatus } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    balance: number;
    packages: SessionPackage[];
    purchases: SessionPackagePurchase[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Session Packages', href: route('session-packages.index') }];

const statusPillClasses: Record<SessionPackagePurchaseStatus, string> = {
    pending: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    paid: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
    cancelled: 'bg-muted text-muted-foreground',
    failed: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
};

function formatPrice(price: number, currency: string): string {
    return price.toLocaleString(undefined, { style: 'currency', currency: currency.toUpperCase() });
}
</script>

<template>
    <Head title="Session Packages" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">Your Session Credits</p>
                <p class="text-2xl font-bold">{{ balance }} {{ balance === 1 ? 'credit' : 'credits' }} remaining</p>
                <p class="mt-1 text-sm text-muted-foreground">Each scheduled session uses one credit. Buy a package below to top up.</p>
            </div>

            <div class="space-y-3">
                <h2 class="text-sm font-semibold text-muted-foreground">Packages</h2>
                <p v-if="packages.length === 0" class="text-sm text-muted-foreground">No packages are available for purchase right now.</p>
                <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="pkg in packages" :key="pkg.id">
                        <CardHeader>
                            <CardTitle>{{ pkg.name }}</CardTitle>
                        </CardHeader>
                        <CardContent class="flex flex-col gap-3">
                            <p v-if="pkg.description" class="text-sm text-muted-foreground">{{ pkg.description }}</p>
                            <p class="text-lg font-semibold">{{ formatPrice(pkg.price, pkg.currency) }}</p>
                            <p class="text-sm text-muted-foreground">{{ pkg.session_count }} sessions</p>
                            <Button as="a" :href="route('session-packages.checkout', pkg.id)" class="w-fit">Buy</Button>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div class="space-y-3">
                <h2 class="text-sm font-semibold text-muted-foreground">Purchase History</h2>
                <p v-if="purchases.length === 0" class="text-sm text-muted-foreground">You haven't purchased any packages yet.</p>
                <div v-else class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <table class="w-full divide-y divide-sidebar-border/70 text-sm dark:divide-sidebar-border">
                        <thead class="bg-muted/50 text-left text-xs text-muted-foreground">
                            <tr>
                                <th class="p-3">Package</th>
                                <th class="p-3">Sessions</th>
                                <th class="p-3">Amount</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/70 bg-card dark:divide-sidebar-border">
                            <tr v-for="purchase in purchases" :key="purchase.id">
                                <td class="p-3 font-medium">{{ purchase.package_name }}</td>
                                <td class="p-3">{{ purchase.session_count }}</td>
                                <td class="p-3">{{ formatPrice(purchase.price, purchase.currency) }}</td>
                                <td class="p-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="statusPillClasses[purchase.status]">
                                        {{ purchase.status }}
                                    </span>
                                </td>
                                <td class="p-3 text-muted-foreground">{{ new Date(purchase.created_at).toLocaleDateString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
