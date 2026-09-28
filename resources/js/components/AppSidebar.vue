<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { BarChart3, LayoutGrid, Map, School } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'My profile',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (page.props.auth.user.role === 'teacher') {
        items.push({
            title: 'Classes',
            href: '/teacher/classes',
            icon: School,
        });
        items.push({
            title: 'Missions',
            href: '/teacher/missions',
            icon: Map,
        });
        items.push({
            title: 'Tracking',
            href: '/teacher/tracking',
            icon: BarChart3,
        });
    }

    if (page.props.auth.user.role === 'student') {
        items.push({
            title: 'My missions',
            href: '/student/missions',
            icon: Map,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
