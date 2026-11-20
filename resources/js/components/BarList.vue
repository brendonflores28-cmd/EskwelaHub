<script setup lang="ts">
import { computed } from 'vue';

/**
 * Horizontal bars with the label on the left and the number on the right.
 * Used for short lists (status, strand, ratings) where long labels would not fit under a column.
 */
type Row = { label: string; value: number; hint?: string; tone?: 'default' | 'good' | 'bad' | 'neutral' };

const props = defineProps<{ rows: Row[]; max?: number; unit?: string }>();

const top = computed(() => props.max ?? Math.max(1, ...props.rows.map((r) => r.value)));
const color = (tone: Row['tone']) =>
    tone === 'good' ? 'bg-green-600' : tone === 'bad' ? 'bg-red-600' : tone === 'neutral' ? 'bg-gray-400' : 'bg-primary';
</script>

<template>
    <ul class="space-y-2">
        <li v-for="r in rows" :key="r.label" class="grid grid-cols-[8.5rem_1fr_3rem] items-center gap-3 text-sm" :title="r.hint ?? `${r.label}: ${r.value}${unit ?? ''}`">
            <span class="truncate">{{ r.label }}</span>
            <span class="h-3 bg-muted"><span class="block h-full" :class="color(r.tone)" :style="{ width: Math.min(100, (r.value / top) * 100) + '%' }"></span></span>
            <span class="text-right font-semibold tabular-nums">{{ r.value }}{{ unit ?? '' }}</span>
        </li>
    </ul>
</template>
