<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, FileText, GraduationCap, KeyRound, LayoutGrid, LayoutList, MessageSquareText, ScanLine, ScrollText, TriangleAlert, UserCheck, UserPlus } from '@lucide/vue';
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
        items.push({ title: 'Student records', href: '/staff/students', icon: GraduationCap, tour: 'nav-records' });
    }
    if (can.viewStudents) {
        items.push({ title: 'Class sections', href: '/staff/sections', icon: LayoutList, tour: 'nav-sections' });
    }
    if (can.processRequests) {
        items.push({ title: 'Document requests', href: '/staff/requests', icon: FileText, tour: 'nav-requests' });
    }
    if (can.reviewEnrollments) {
        items.push({ title: 'Enrollment forms', href: '/staff/enrollments', icon: UserPlus, tour: 'nav-enrollments' });
    }
    if (can.useOcr) {
        items.push({ title: 'Digitize records', href: '/staff/ocr', icon: ScanLine, tour: 'nav-ocr' });
    }
    if (can.reviewLinks) {
        items.push({ title: 'Account link approvals', href: '/staff/links', icon: UserCheck, tour: 'nav-links' });
    }
    if (can.viewStudents) {
        items.push({ title: 'Record alerts', href: '/staff/alerts', icon: TriangleAlert, tour: 'nav-alerts' });
    }
    if (can.viewSurveys) {
        items.push({ title: 'Surveys', href: '/admin/surveys', icon: MessageSquareText, tour: 'nav-surveys' });
    }
    if (can.viewAudit) {
        items.push({ title: 'Audit trail', href: '/admin/audit-log', icon: ScrollText, tour: 'nav-audit' });
    }
    if (can.viewSessions) {
        items.push({ title: 'Session logs', href: '/admin/session-logs', icon: KeyRound, tour: 'nav-sessions' });
    }
    if (!can.viewStudents) {
        items.push({ title: 'My records', href: '/my-records', icon: ClipboardList, tour: 'nav-records' });
        items.push({ title: 'My requests', href: '/my-requests', icon: FileText, tour: 'nav-requests' });
        items.push({ title: 'Enrollment', href: '/my-enrollments', icon: UserPlus, tour: 'nav-enrollment' });
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
