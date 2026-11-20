<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/records';

defineProps<{ page: Paginated<unknown> }>();
</script>

<template>
    <div class="flex items-center justify-between text-sm text-muted-foreground">
        <span v-if="page.meta.total > 0">
            Showing {{ page.meta.from }} to {{ page.meta.to }} of
            {{ page.meta.total }}
        </span>
        <span v-else>No results</span>

        <div class="flex gap-2">
            <Button v-if="page.links.prev" variant="outline" size="sm" as-child>
                <Link :href="page.links.prev" preserve-scroll>Previous</Link>
            </Button>
            <Button v-if="page.links.next" variant="outline" size="sm" as-child>
                <Link :href="page.links.next" preserve-scroll>Next</Link>
            </Button>
        </div>
    </div>
</template>
