<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart3, CalendarClock, ClipboardList, GraduationCap, LayoutGrid, LifeBuoy, MessagesSquare, Trophy, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage<SharedData>();
const roles = computed(() => page.props.auth.user?.roles ?? []);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'Community',
            href: route('community.index'),
            icon: MessagesSquare,
        },
    ];

    if (roles.value.includes('teacher')) {
        items.push({
            title: 'Sessions Schedule',
            href: route('teaching.sessions-schedule'),
            icon: CalendarClock,
        });
        items.push({
            title: 'LMS',
            href: route('lms.index'),
            icon: GraduationCap,
        });
        items.push({
            title: 'Insights',
            href: route('insights.index'),
            icon: BarChart3,
        });
        items.push({
            title: 'Payroll',
            href: route('payroll.index'),
            icon: Wallet,
        });
        items.push({
            title: 'Requests',
            href: route('requests.index'),
            icon: ClipboardList,
        });
    }

    if (roles.value.includes('student')) {
        items.push({
            title: 'Achievements',
            href: route('achievements.index'),
            icon: Trophy,
        });
    }

    return items;
});

const helpNavItem: NavItem = {
    title: 'Help',
    href: route('help.index'),
    icon: LifeBuoy,
};
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu class="group-data-[collapsible=icon]:p-0">
                <SidebarMenuItem>
                    <SidebarMenuButton class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100" as-child>
                        <Link :href="helpNavItem.href">
                            <component :is="helpNavItem.icon" />
                            <span>{{ helpNavItem.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
