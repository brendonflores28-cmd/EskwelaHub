<script setup lang="ts">
import { computed } from 'vue';

/**
 * Vertical bar chart in plain HTML. Layout rules so nothing overlaps:
 *  - every bar sits in its own column, the value is printed right above the bar
 *    and the plot always keeps 22 px of head room for it (no clipped numbers);
 *  - the label is under the bar, one line, shortened with "..." (full text on hover);
 *  - columns have a minimum width and the chart scrolls sideways instead of squeezing.
 * Kinds: "actual" (solid), "current" (dark green) and "forecast" (striped).
 */
type Item = { label: string; value: number; kind?: 'actual' | 'current' | 'forecast'; hint?: string };

const props = defineProps<{ items: Item[]; height?: number }>();

const plot = computed(() => props.height ?? 190);
const max = computed(() => Math.max(1, ...props.items.map((i) => i.value)));
const barHeight = (v: number) => Math.max(2, Math.round((v / max.value) * (plot.value - 24)));

const style = (kind: Item['kind']) =>
    kind === 'forecast'
        ? 'background: repeating-linear-gradient(45deg, rgba(13,107,37,.18) 0 5px, rgba(13,107,37,.34) 5px 10px); border: 1px dashed var(--primary);'
        : kind === 'current'
          ? 'background: var(--primary);'
          : 'background: color-mix(in oklab, var(--primary) 62%, white);';
</script>

<template>
    <div class="overflow-x-auto pb-1">
        <div class="flex items-end gap-1.5" :style="{ minWidth: items.length * 44 + 'px' }" role="img" :aria-label="items.map((i) => `${i.label}: ${i.value}`).join(', ')">
            <div v-for="item in items" :key="item.label" class="flex min-w-0 flex-1 flex-col items-stretch" :title="item.hint ?? `${item.label}: ${item.value}`">
                <div class="flex flex-col items-center justify-end" :style="{ height: plot + 'px' }">
                    <span class="mb-1 text-xs font-semibold tabular-nums">{{ item.value }}</span>
                    <div class="w-full" :style="[{ height: barHeight(item.value) + 'px' }, style(item.kind)]"></div>
                </div>
                <div class="mt-1 truncate border-t pt-1 text-center text-[11px] text-muted-foreground">{{ item.label }}</div>
            </div>
        </div>
    </div>
</template>
