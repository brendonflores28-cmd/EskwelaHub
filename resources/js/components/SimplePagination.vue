<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

/**
 * Previous / Next buttons centered at the bottom of a list, with the page
 * number between them and the result count below. A button that has nowhere
 * to go stays visible but disabled, so the buttons never jump around.
 *
 * Works with both shapes Laravel sends: a resource collection (links + meta)
 * and a plain paginator (prev_page_url, next_page_url, total...).
 */
type Shape = {
    links?: { prev: string | null; next: string | null };
    meta?: { current_page: number; last_page: number; total: number; from: number | null; to: number | null };
    prev_page_url?: string | null;
    next_page_url?: string | null;
    current_page?: number;
    last_page?: number;
    total?: number;
    from?: number | null;
    to?: number | null;
};

const props = defineProps<{ page: Shape }>();

const info = computed(() => ({
    prev: props.page.links?.prev ?? props.page.prev_page_url ?? null,
    next: props.page.links?.next ?? props.page.next_page_url ?? null,
    current: props.page.meta?.current_page ?? props.page.current_page ?? 1,
    last: props.page.meta?.last_page ?? props.page.last_page ?? 1,
    total: props.page.meta?.total ?? props.page.total ?? 0,
    from: props.page.meta?.from ?? props.page.from ?? null,
    to: props.page.meta?.to ?? props.page.to ?? null,
}));
</script>

<template>
    <nav class="flex flex-col items-center gap-2 pt-2 pb-16" aria-label="Pagination">
        <div class="flex items-center gap-3">
            <Button v-if="info.prev" variant="outline" size="sm" as-child>
                <Link :href="info.prev" preserve-scroll><ChevronLeft class="size-4" /> Previous</Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled><ChevronLeft class="size-4" /> Previous</Button>

            <span class="min-w-24 text-center text-sm font-medium">Page {{ info.current }} of {{ info.last }}</span>

            <Button v-if="info.next" variant="outline" size="sm" as-child>
                <Link :href="info.next" preserve-scroll>Next <ChevronRight class="size-4" /></Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>Next <ChevronRight class="size-4" /></Button>
        </div>

        <p class="text-sm text-muted-foreground">
            <template v-if="info.total > 0">Showing {{ info.from }} to {{ info.to }} of {{ info.total }}</template>
            <template v-else>No results</template>
        </p>
    </nav>
</template>
