<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { BarChart3, LayoutGrid, Map, School, Shirt } from '@lucide/vue';
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
            title: 'Mi perfil',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (page.props.auth.user.role === 'teacher') {
        items.push({
            title: 'Clases',
            href: '/teacher/classes',
            icon: School,
        });
        items.push({
            title: 'Misiones',
            href: '/teacher/missions',
            icon: Map,
        });
        items.push({
            title: 'Seguimiento',
            href: '/teacher/tracking',
            icon: BarChart3,
        });
    }

    if (page.props.auth.user.role === 'student') {
        items.push({
            title: 'Mis misiones',
            href: '/student/missions',
            icon: Map,
        });
        items.push({
            title: 'Avatar y tienda',
            href: '/student/avatar',
            icon: Shirt,
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
