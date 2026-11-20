<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, watch } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { buildBreadcrumbs } from '@/lib/breadcrumbs';
import { roleFromAuth, startTour, tourDone, tourFor } from '@/lib/tour';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();

// Full trail from the address (Dashboard > Section > Record > Edit), so every page shows where you are
// and links back. Pages without a rule keep the breadcrumbs they define themselves.
const trail = computed(() => buildBreadcrumbs(page.url, page.props as Record<string, unknown>) ?? props.breadcrumbs);

/**
 * First visit of the dashboard: the tour of the person's role starts by itself, once.
 * (Remembered in this browser; the "Tour" button in the top bar starts it again.)
 */
function maybeStartTour(): void {
    const user = page.props.auth.user;
    document.body.dataset.userId = String(user.id);

    if (page.url.split('?')[0] === '/dashboard' && !tourDone(user.id)) {
        setTimeout(() => startTour({ steps: tourFor(roleFromAuth(page.props.auth.can)), id: 'main' }), 900);
    }
}

onMounted(maybeStartTour);
watch(() => page.url, maybeStartTour);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="trail" />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
