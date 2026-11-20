<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CircleHelp, ClipboardList, FileText, GraduationCap, LayoutGrid, MessageSquareText, ScanLine, ScrollText, TriangleAlert, UserCheck, UserPlus } from '@lucide/vue';
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
    if (can.processRequests) {
        items.push({ title: 'Document requests', href: '/staff/requests', icon: FileText });
    }
    if (can.reviewEnrollments) {
        items.push({ title: 'Enrollment forms', href: '/staff/enrollments', icon: UserPlus });
    }
    if (can.useOcr) {
        items.push({ title: 'Digitize records', href: '/staff/ocr', icon: ScanLine });
    }
    if (can.reviewLinks) {
        items.push({ title: 'Account link approvals', href: '/staff/links', icon: UserCheck });
    }
    if (can.viewStudents) {
        items.push({ title: 'Record alerts', href: '/staff/alerts', icon: TriangleAlert });
    }
    if (can.viewSurveys) {
        items.push({ title: 'Surveys', href: '/admin/surveys', icon: MessageSquareText });
    }
    if (can.viewAudit) {
        items.push({ title: 'Audit log', href: '/admin/audit-log', icon: ScrollText });
    }
    if (!can.viewStudents) {
        items.push({ title: 'My records', href: '/my-records', icon: ClipboardList });
        items.push({ title: 'My requests', href: '/my-requests', icon: FileText });
        items.push({ title: 'Enrollment', href: '/my-enrollments', icon: UserPlus });
    }

    items.push({ title: 'Help', href: '/help', icon: CircleHelp });

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
