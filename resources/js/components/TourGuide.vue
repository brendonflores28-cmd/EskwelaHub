<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, X } from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { markTourDone, preferredLanguage, savePreferredLanguage, TOUR_EVENT } from '@/lib/tour';
import type { TourDetail, TourStep } from '@/lib/tour';

/**
 * The tour overlay. It dims the page, draws a gold frame around the element of
 * the current step and shows a short text. Clicks are NOT blocked, so a person can
 * also press the highlighted button itself (used when the assistant teaches).
 */
const steps = ref<TourStep[]>([]);
const index = ref(0);
const open = ref(false);
const tourId = ref<string | undefined>();
const lang = ref<'en' | 'tl'>('en');
const rect = ref<{ top: number; left: number; width: number; height: number } | null>(null);
const card = ref<HTMLElement | null>(null);
const cardStyle = ref<Record<string, string>>({});

const isPhone = () => window.matchMedia('(max-width: 767px)').matches;

/** Element of a step, if it is on the screen right now. */
function find(step: TourStep): HTMLElement | null {
    if (!step.target) return null;
    const el = document.querySelector<HTMLElement>(`[data-tour="${step.target}"]`);
    if (!el) return null;
    const r = el.getBoundingClientRect();

    return r.width > 0 && r.height > 0 ? el : null;
}

const step = computed(() => steps.value[index.value]);
const title = computed(() => (lang.value === 'tl' ? (step.value?.title_tl ?? step.value?.title) : step.value?.title));
const text = computed(() => (lang.value === 'tl' ? (step.value?.text_tl ?? step.value?.text) : step.value?.text));
const last = computed(() => index.value >= steps.value.length - 1);

async function waitFor(s: TourStep, ms = 4000): Promise<HTMLElement | null> {
    const end = Date.now() + ms;
    while (Date.now() < end) {
        const el = find(s);
        if (el) return el;
        await new Promise((r) => setTimeout(r, 150));
    }

    return null;
}

/** Move to a step. Steps for the other device size or without a visible element are skipped. */
async function go(to: number, direction: 1 | -1 = 1): Promise<void> {
    let i = to;
    while (i >= 0 && i < steps.value.length) {
        const s = steps.value[i];
        const wrongDevice = (s.only === 'mobile' && !isPhone()) || (s.only === 'desktop' && isPhone());

        if (!wrongDevice) {
            const path = s.url?.split('?')[0];
            if (s.url && path && (path === '/' ? location.pathname !== '/' : !location.pathname.startsWith(path))) {
                router.visit(s.url);
                await new Promise((r) => setTimeout(r, 300));
            }
            if (!s.target || (await waitFor(s, s.url ? 4000 : 400))) break;
        }
        i += direction;
    }

    if (i < 0 || i >= steps.value.length) {
        if (direction === 1) finish();
        return;
    }

    index.value = i;
    await nextTick();
    const el = find(steps.value[i]);
    el?.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'smooth' });
    setTimeout(place, 350); // after the smooth scroll
    place();
}

function place(): void {
    if (!open.value || !step.value) return;
    const el = find(step.value);
    const vw = window.innerWidth;
    const vh = window.innerHeight;

    if (!el) {
        rect.value = null;
        cardStyle.value = { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' };
        return;
    }

    const r = el.getBoundingClientRect();
    rect.value = { top: r.top - 6, left: r.left - 6, width: r.width + 12, height: r.height + 12 };

    if (isPhone()) {
        // On a phone the card sits at the top or the bottom, whichever side the element is not on.
        cardStyle.value = r.top + r.height / 2 > vh / 2 ? { top: '12px', left: '12px', right: '12px' } : { bottom: '12px', left: '12px', right: '12px' };
        return;
    }

    const w = 352;
    const h = card.value?.offsetHeight ?? 180;
    let top = r.bottom + 14;
    let left = r.left;

    if (r.left < 300 && r.width < 320) {
        // An item of the sidebar: the card goes to its right.
        top = r.top - 8;
        left = r.right + 18;
    } else if (top + h > vh - 12) {
        top = Math.max(12, r.top - h - 14);
    }

    left = Math.min(Math.max(12, left), vw - w - 12);
    top = Math.min(Math.max(12, top), vh - h - 12);
    cardStyle.value = { top: `${top}px`, left: `${left}px`, width: `${w}px` };
}

function begin(detail: TourDetail): void {
    // Drop steps made for the other screen size so "Step 2 / 8" counts only what is shown.
    steps.value = detail.steps.filter((s) => !((s.only === 'mobile' && !isPhone()) || (s.only === 'desktop' && isPhone())));
    tourId.value = detail.id;
    lang.value = detail.lang ?? preferredLanguage();
    open.value = true;
    index.value = 0;
    void go(0);
}

function finish(): void {
    open.value = false;
    rect.value = null;
    if (tourId.value === 'main') {
        const id = (document.body.dataset.userId ?? '') || 'guest';
        markTourDone(id);
    }
}

function setLang(next: 'en' | 'tl'): void {
    lang.value = next;
    savePreferredLanguage(next);
    void nextTick(place);
}

function onKey(e: KeyboardEvent): void {
    if (!open.value) return;
    if (e.key === 'Escape') finish();
    if (e.key === 'ArrowRight' || e.key === 'Enter') last.value ? finish() : void go(index.value + 1);
    if (e.key === 'ArrowLeft') void go(index.value - 1, -1);
}

const onTour = (e: Event) => begin((e as CustomEvent<TourDetail>).detail);

onMounted(() => {
    window.addEventListener(TOUR_EVENT, onTour);
    window.addEventListener('resize', place);
    window.addEventListener('scroll', place, true);
    window.addEventListener('keydown', onKey);
});
onBeforeUnmount(() => {
    window.removeEventListener(TOUR_EVENT, onTour);
    window.removeEventListener('resize', place);
    window.removeEventListener('scroll', place, true);
    window.removeEventListener('keydown', onKey);
});
</script>

<template>
    <div v-if="open" class="pointer-events-none fixed inset-0 z-[70]" role="dialog" aria-modal="false" aria-label="Guided tour">
        <!-- The frame around the element. Its huge shadow dims everything else. -->
        <div
            v-if="rect"
            class="fixed border-[3px] border-[#c9a227] transition-all duration-200"
            :style="{ top: rect.top + 'px', left: rect.left + 'px', width: rect.width + 'px', height: rect.height + 'px', boxShadow: '0 0 0 9999px rgba(4, 36, 14, 0.62)' }"
        ></div>
        <div v-else class="fixed inset-0 bg-[#04240e]/60"></div>

        <section ref="card" class="pointer-events-auto fixed border bg-background text-foreground shadow-2xl" :style="cardStyle">
            <header class="flex items-center justify-between gap-2 bg-primary px-3 py-2 text-primary-foreground">
                <span class="text-xs font-semibold tracking-wide uppercase">
                    {{ lang === 'tl' ? 'Hakbang' : 'Step' }} {{ index + 1 }} / {{ steps.length }}
                </span>
                <span class="flex items-center gap-1">
                    <button type="button" :class="['px-1.5 py-0.5 text-xs', lang === 'en' ? 'bg-white/25' : 'opacity-70 hover:opacity-100']" @click="setLang('en')">EN</button>
                    <button type="button" :class="['px-1.5 py-0.5 text-xs', lang === 'tl' ? 'bg-white/25' : 'opacity-70 hover:opacity-100']" @click="setLang('tl')">TL</button>
                    <button type="button" class="ml-1 p-0.5 hover:bg-white/20" :aria-label="lang === 'tl' ? 'Isara' : 'Close'" @click="finish"><X class="size-4" /></button>
                </span>
            </header>
            <div class="px-4 py-3">
                <h2 v-if="title" class="font-serif text-base font-bold">{{ title }}</h2>
                <p class="text-sm" :class="title ? 'mt-1' : ''">{{ text }}</p>
            </div>
            <footer class="flex items-center justify-between gap-2 border-t px-3 py-2">
                <button type="button" class="text-xs text-muted-foreground hover:underline" @click="finish">{{ lang === 'tl' ? 'Laktawan' : 'Skip' }}</button>
                <span class="flex gap-2">
                    <button v-if="index > 0" type="button" class="flex items-center border px-2.5 py-1 text-sm hover:bg-muted" @click="go(index - 1, -1)">
                        <ChevronLeft class="size-4" /> {{ lang === 'tl' ? 'Bumalik' : 'Back' }}
                    </button>
                    <button type="button" class="flex items-center bg-primary px-3 py-1 text-sm font-medium text-primary-foreground hover:opacity-90" @click="last ? finish() : go(index + 1)">
                        {{ last ? (lang === 'tl' ? 'Tapos na' : 'Done') : lang === 'tl' ? 'Susunod' : 'Next' }}
                        <ChevronRight v-if="!last" class="size-4" />
                    </button>
                </span>
            </footer>
        </section>
    </div>
</template>
