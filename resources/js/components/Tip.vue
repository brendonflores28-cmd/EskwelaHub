<script setup lang="ts">
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

/**
 * Hover tooltip: wrap any element, give it a short text.
 *   <Tip text="Download the list as an Excel file"><Button>Export</Button></Tip>
 */
defineProps<{ text: string; side?: 'top' | 'right' | 'bottom' | 'left' }>();
</script>

<template>
    <TooltipProvider :delay-duration="250">
        <Tooltip>
            <TooltipTrigger as-child><slot /></TooltipTrigger>
            <TooltipContent :side="side ?? 'top'" class="max-w-64 text-center">{{ text }}</TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
