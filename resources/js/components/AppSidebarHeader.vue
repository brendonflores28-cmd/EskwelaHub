<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { CircleHelp } from '@lucide/vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import Tip from '@/components/Tip.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { roleFromAuth, startTour, tourFor } from '@/lib/tour';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();

/** Start the tour of this person's role again. */
function tour(): void {
    startTour({ steps: tourFor(roleFromAuth(page.props.auth.can)), id: 'main' });
}
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <Tip text="Open or close the menu" side="bottom">
                <SidebarTrigger class="-ml-1" data-tour="menu-button" />
            </Tip>
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <Tip text="Take a quick tour of this page" side="bottom">
            <button type="button" class="flex items-center gap-1.5 border px-2.5 py-1.5 text-sm hover:bg-muted" data-tour="tour-button" @click="tour">
                <CircleHelp class="size-4" /> <span class="hidden sm:inline">Tour</span>
            </button>
        </Tip>
    </header>
</template>
