<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!-- Official seal of Cawayan National High School (public/images). Round, so the white corners are clipped. -->
    <img
        src="/images/cnhs-logo-96.png"
        srcset="/images/cnhs-logo-96.png 1x, /images/cnhs-logo-256.png 2x"
        alt="Cawayan National High School seal"
        :class="['rounded-full object-contain', className]"
        v-bind="$attrs"
    />
</template>
