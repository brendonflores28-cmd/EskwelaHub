<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { TriangleAlert } from '@lucide/vue';

/** Warning shown to staff when a look-alike record already exists. */
defineProps<{ similar: { id: number; full_name: string; lrn: string | null; birthdate: string }[] }>();
</script>

<template>
    <div v-if="similar.length" class="border border-amber-500/50 bg-amber-50 p-4 text-sm dark:bg-amber-950/30">
        <div class="flex items-center gap-2 font-medium">
            <TriangleAlert class="size-4 text-amber-600" /> A similar record already exists
        </div>
        <p class="mt-1 text-muted-foreground">Check these before saving, so the learner does not get two records.</p>
        <ul class="mt-2 space-y-1">
            <li v-for="s in similar" :key="s.id">
                <Link :href="`/staff/students/${s.id}`" class="underline" target="_blank">{{ s.full_name }}</Link>
                - born {{ s.birthdate }}, LRN {{ s.lrn ?? 'none' }}
            </li>
        </ul>
    </div>
</template>
