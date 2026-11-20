<script setup lang="ts">
/**
 * Plain <select> styled like the other inputs. Used for filters and forms
 * where the options are simple lists (school years, status, grade).
 */
defineProps<{
    options: { value: string | number; label: string }[];
    placeholder?: string;
}>();

const model = defineModel<string | number | null>();
</script>

<template>
    <select
        v-model="model"
        class="h-9 w-full border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
    >
        <option v-if="placeholder !== undefined" :value="''">
            {{ placeholder }}
        </option>
        <option v-for="o in options" :key="o.value" :value="o.value">
            {{ o.label }}
        </option>
    </select>
</template>
