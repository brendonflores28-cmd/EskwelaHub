<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/records';

/**
 * Previous / Next buttons centered at the bottom of a list, with the page
 * number between them and the result count below. A button that has nowhere
 * to go stays visible but disabled, so the buttons never jump around.
 */
defineProps<{ page: Paginated<unknown> }>();
</script>

<template>
    <nav class="flex flex-col items-center gap-2 pt-2 pb-16" aria-label="Pagination">
        <div class="flex items-center gap-3">
            <Button v-if="page.links.prev" variant="outline" size="sm" as-child>
                <Link :href="page.links.prev" preserve-scroll><ChevronLeft class="size-4" /> Previous</Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled><ChevronLeft class="size-4" /> Previous</Button>

            <span class="min-w-24 text-center text-sm font-medium">Page {{ page.meta.current_page }} of {{ page.meta.last_page }}</span>

            <Button v-if="page.links.next" variant="outline" size="sm" as-child>
                <Link :href="page.links.next" preserve-scroll>Next <ChevronRight class="size-4" /></Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>Next <ChevronRight class="size-4" /></Button>
        </div>

        <p class="text-sm text-muted-foreground">
            <template v-if="page.meta.total > 0">Showing {{ page.meta.from }} to {{ page.meta.to }} of {{ page.meta.total }}</template>
            <template v-else>No results</template>
        </p>
    </nav>
</template>
