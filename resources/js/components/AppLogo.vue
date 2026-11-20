<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
</script>

<template>
    <AppLogoIcon class-name="size-9 shrink-0" />
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="truncate leading-tight font-semibold">{{ page.props.name }}</span>
        <span class="truncate text-[11px] leading-tight text-muted-foreground">{{ page.props.school.short_name }}</span>
    </div>
</template>
