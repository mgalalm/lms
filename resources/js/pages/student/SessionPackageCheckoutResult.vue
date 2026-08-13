<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    outcome: 'success';
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Session Packages', href: route('session-packages.index') }];
</script>

<template>
    <Head title="Payment received" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <Card class="max-w-lg">
                <CardHeader>
                    <CardTitle>Payment received</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-4">
                    <p class="text-sm text-muted-foreground">
                        Thanks! Your payment is being confirmed and your session credits will appear on your balance shortly.
                    </p>
                    <Button as-child class="w-fit">
                        <Link :href="route('session-packages.index')">Back to Session Packages</Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
