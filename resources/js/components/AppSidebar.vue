<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, GraduationCap, LayoutGrid, ScrollText, UserCheck } from '@lucide/vue';
import { computed } from 'vue';
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

/**
 * The menu follows the user's portal. This only hides links: every route is
 * also protected on the server by permissions and policies.
 */
const mainNavItems = computed<NavItem[]>(() => {
    const can = page.props.auth.can;
    const items: NavItem[] = [{ title: 'Dashboard', href: dashboard(), icon: LayoutGrid }];

    if (can.viewStudents) {
        items.push({ title: 'Student records', href: '/staff/students', icon: GraduationCap });
    }
    if (can.reviewLinks) {
        items.push({ title: 'Link requests', href: '/staff/links', icon: UserCheck });
    }
    if (can.viewAudit) {
        items.push({ title: 'Audit log', href: '/admin/audit-log', icon: ScrollText });
    }
    if (!can.viewStudents) {
        items.push({ title: 'My records', href: '/my-records', icon: ClipboardList });
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
