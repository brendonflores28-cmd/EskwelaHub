<script setup lang="ts">
import type { HistoryEntry } from '@/types/records';

/** Status history of a request, oldest first. */
defineProps<{ history: HistoryEntry[] }>();

const label = (status: string) => status.replace('_', ' ');
</script>

<template>
    <ol class="space-y-3 border-l pl-4">
        <li v-for="(h, i) in history" :key="i" class="relative">
            <span class="absolute -left-[21px] top-1.5 size-2.5 bg-primary"></span>
            <div class="text-sm font-medium capitalize">{{ label(h.to_status) }}</div>
            <div class="text-xs text-muted-foreground">{{ h.at }} - {{ h.by }}</div>
            <div v-if="h.note" class="mt-1 text-sm">{{ h.note }}</div>
        </li>
    </ol>
</template>
