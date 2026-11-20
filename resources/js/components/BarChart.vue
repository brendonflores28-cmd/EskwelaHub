<script setup lang="ts">
import { computed } from 'vue';

/**
 * Small bar chart made of plain HTML (no chart library to download).
 * Bars flagged `projected` are striped, so a forecast is never mistaken for a fact.
 */
const props = defineProps<{
    items: { label: string; value: number; projected?: boolean; highlight?: boolean }[];
    height?: number;
}>();

const max = computed(() => Math.max(1, ...props.items.map((i) => i.value)));
const barArea = computed(() => props.height ?? 160);
</script>

<template>
    <div class="overflow-x-auto">
        <div class="flex min-w-fit items-end gap-2" :style="{ height: barArea + 28 + 'px' }" role="img" :aria-label="items.map((i) => `${i.label}: ${i.value}`).join(', ')">
            <div v-for="item in items" :key="item.label" class="flex w-12 shrink-0 flex-col items-center justify-end gap-1">
                <span class="text-xs font-medium">{{ item.value }}</span>
                <div
                    class="w-full "
                    :class="[
 item.projected ? 'border border-dashed border-primary bg-primary/20' : item.highlight ? 'bg-primary' : 'bg-primary/70',
                    ]"
                    :style="{ height: Math.max(2, (item.value / max) * barArea) + 'px' }"
                    :title="`${item.label}: ${item.value}${item.projected ? ' (forecast)' : ''}`"
                ></div>
                <span class="w-full truncate text-center text-[10px] text-muted-foreground">{{ item.label }}</span>
            </div>
        </div>
    </div>
</template>
