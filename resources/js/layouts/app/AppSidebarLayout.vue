<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { buildBreadcrumbs } from '@/lib/breadcrumbs';
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
