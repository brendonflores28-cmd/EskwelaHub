<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home, login, register } from '@/routes';

/**
 * Log in, register and password pages are shown as a modal dialog over the
 * school photo with its green cover (same backdrop as the home page).
 * The X (or a click outside the card) goes back to the home page.
 */
defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
const school = page.props.school;

// The Log in / Register tabs appear only on those two pages.
const path = computed(() => page.url.split('?')[0]);
const showTabs = computed(() => path.value === '/login' || path.value === '/register');
const onRegister = computed(() => path.value === '/register');
</script>

<template>
    <div class="relative isolate flex min-h-svh items-center justify-center overflow-hidden p-4 md:p-8">
        <!-- Backdrop: school photo under transparent green. Clicking it closes the dialog. -->
        <Link :href="home()" class="absolute inset-0 -z-10" aria-label="Close and go to the home page" tabindex="-1">
            <span class="absolute inset-0 bg-cover bg-center" style="background-image: url('/images/school-hero.jpg'), url('/images/hero-scenery.jpg')"></span>
            <span class="absolute inset-0 bg-gradient-to-br from-[#0a4d1a]/90 via-[#0d6b25]/80 to-[#0d6b25]/60 backdrop-blur-[2px]"></span>
        </Link>

        <!-- Dialog -->
        <section
            role="dialog"
            aria-modal="true"
            :aria-label="title || 'Account'"
            class="relative flex max-h-[calc(100svh-2rem)] w-full max-w-md flex-col overflow-hidden bg-background text-foreground shadow-xl border"
        >
            <div class="h-1.5 shrink-0 bg-gold"></div>

            <Link
                :href="home()"
                class="absolute top-4 right-3 p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground"
                aria-label="Close"
            >
                <X class="size-5" />
            </Link>

            <div class="overflow-y-auto px-6 pt-6 pb-7 sm:px-8">
                <!-- School identity -->
                <div class="flex items-center gap-3 pr-8">
                    <AppLogoIcon class-name="size-12 shrink-0" />
                    <div class="leading-tight">
                        <div class="font-serif text-base font-bold">{{ school.name }}</div>
                        <div class="text-xs tracking-wide text-muted-foreground uppercase">{{ page.props.name }} &middot; School Portal</div>
                    </div>
                </div>

                <!-- Log in / Register tabs -->
                <div v-if="showTabs" class="mt-6 grid grid-cols-2 bg-muted p-1 text-sm font-medium" role="tablist">
                    <Link
                        :href="login()"
                        role="tab"
                        :aria-selected="!onRegister"
                        :class="[' py-2 text-center transition', !onRegister ? 'bg-background text-primary shadow-sm' : 'text-muted-foreground hover:text-foreground']"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        role="tab"
                        :aria-selected="onRegister"
                        :class="[' py-2 text-center transition', onRegister ? 'bg-background text-primary shadow-sm' : 'text-muted-foreground hover:text-foreground']"
                    >
                        Register
                    </Link>
                </div>

                <div class="mt-6 space-y-1">
                    <h1 class="font-serif text-2xl font-bold">{{ title }}</h1>
                    <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
                </div>

                <div class="mt-6" data-tour="auth-form">
                    <slot />
                </div>
            </div>
        </section>
    </div>
</template>
