<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { relativeDate } from '@/composables/relativeDate';
import type { SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();

const unreadCount = computed(() => page.props.unreadNotificationsCount);
const notifications = computed(() => page.props.notifications ?? []);

function onOpenChange(open: boolean): void {
    if (open) {
        router.reload({ only: ['notifications'], preserveState: true, preserveScroll: true });
    }
}

function markAllRead(): void {
    router.post(
        route('notifications.read-all'),
        {},
        { preserveState: true, preserveScroll: true, only: ['notifications', 'unreadNotificationsCount'] },
    );
}
</script>

<template>
    <DropdownMenu @update:open="onOpenChange">
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative h-9 w-9" aria-label="Notifications">
                <Bell class="size-5" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-medium leading-none text-white"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-80" side="bottom" align="end" :side-offset="4">
            <div class="flex items-center justify-between px-3 py-2">
                <span class="text-sm font-semibold">Notifications</span>
                <button v-if="unreadCount > 0" type="button" class="text-xs text-primary hover:underline" @click="markAllRead">Mark all read</button>
            </div>

            <div class="max-h-80 divide-y divide-sidebar-border/70 overflow-y-auto dark:divide-sidebar-border">
                <p v-if="notifications.length === 0" class="px-3 py-6 text-center text-sm text-muted-foreground">No notifications yet.</p>

                <Link
                    v-for="notification in notifications"
                    :key="notification.id"
                    :href="route('notifications.read', notification.id)"
                    method="post"
                    as="a"
                    class="flex items-start gap-2 px-3 py-2.5 text-sm hover:bg-accent"
                >
                    <span class="mt-1.5 size-1.5 shrink-0 rounded-full" :class="notification.read_at ? 'bg-transparent' : 'bg-primary'" />
                    <span class="flex-1">
                        <span class="block font-medium">{{ notification.title }}</span>
                        <span class="block text-muted-foreground">{{ notification.message }}</span>
                        <span class="block text-xs text-muted-foreground">{{ relativeDate(notification.created_at) }}</span>
                    </span>
                </Link>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
