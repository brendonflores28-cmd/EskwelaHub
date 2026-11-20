<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{ status: string }>();

const variant = computed(() =>
    props.status === 'enrolled' || props.status === 'approved' ? 'default' : 'secondary',
);
</script>

<template>
    <Badge :variant="variant" class="capitalize">{{ status }}</Badge>
</template>
