<script setup lang="ts">
import { Bot, MessageCircle, Send, X } from '@lucide/vue';
import { nextTick, onMounted, ref, watch } from 'vue';
import { preferredLanguage, savePreferredLanguage, startTour, type TourStep } from '@/lib/tour';

/**
 * Floating help assistant (bottom right on every page, also for visitors who
 * are not logged in). It sends the question to POST /chatbot, which answers
 * from the FAQ table, in English or Tagalog.
 */
type Lang = 'en' | 'tl';
type Guide = { id: string; steps: TourStep[] };
type Message = { from: 'me' | 'bot'; text: string; suggestions?: { id: number; question: string }[]; guide?: Guide | null; lang?: Lang };

const STORAGE_KEY = 'eskwelahub-chat';
const text = {
    en: {
        greeting: 'Hi! I am the EskwelaHub assistant. Ask me anything about enrollment, documents or this portal, in English or Tagalog. I can also show you where to click.',
        starters: ['How do I enroll my child in Grade 7?', 'How do I request Form 137?', 'Show me how to log in'],
        placeholder: 'Type your question...',
        sub: 'Enrollment, documents and help',
        newChat: 'New chat',
        privacy: 'Please do not type names, LRNs or other personal details.',
        show: 'Show me where to click',
        again: 'Show me again',
        busy: 'You asked many questions in a short time. Please wait a minute and try again.',
        error: 'Sorry, I cannot answer right now. Please try again in a minute or ask the registrar.',
    },
    tl: {
        greeting: 'Kumusta po! Ako ang EskwelaHub assistant. Magtanong ka tungkol sa enrollment, documents o sa portal na ito, sa English o Tagalog. Maipapakita ko rin kung saan magki-click.',
        starters: ['Paano i-enroll ang anak ko sa Grade 7?', 'Paano humingi ng Form 137?', 'Turuan mo akong mag-log in'],
        placeholder: 'I-type ang tanong mo...',
        sub: 'Enrollment, documents at tulong',
        newChat: 'Bagong chat',
        privacy: 'Huwag maglagay ng pangalan, LRN o personal na detalye.',
        show: 'Ipakita kung saan magki-click',
        again: 'Ipakita ulit',
        busy: 'Marami ka nang naitanong. Maghintay ng isang minuto at subukan ulit.',
        error: 'Pasensya na, hindi ako makasagot ngayon. Subukan ulit mamaya o magtanong sa registrar.',
    },
} as const;

const lang = ref<Lang>(preferredLanguage());
const t = () => text[lang.value];
const greeting = (): Message => ({ from: 'bot', text: t().greeting, lang: lang.value });

function setLang(next: Lang): void {
    lang.value = next;
    savePreferredLanguage(next);
    if (messages.value.length === 1) messages.value = [greeting()];
}

const open = ref(false);
const busy = ref(false);
const question = ref('');
const messages = ref<Message[]>([greeting()]);
const box = ref<HTMLElement | null>(null);
const input = ref<HTMLInputElement | null>(null);

// Keep the conversation while the tab is open (the browser may block storage: then it simply starts fresh).
onMounted(() => {
    try {
        const saved = sessionStorage.getItem(STORAGE_KEY);
        if (saved) messages.value = JSON.parse(saved) as Message[];
    } catch {
        /* ignore */
    }
});

watch(messages, (value) => {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(value.slice(-30)));
    } catch {
        /* ignore */
    }
}, { deep: true });

async function scrollDown(): Promise<void> {
    await nextTick();
    box.value?.scrollTo({ top: box.value.scrollHeight, behavior: 'smooth' });
}

async function toggle(): Promise<void> {
    open.value = !open.value;
    if (open.value) {
        await scrollDown();
        input.value?.focus();
    }
}

/** Laravel checks the XSRF cookie on POST requests, so send it back in a header. */
function xsrf(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

async function ask(text: string): Promise<void> {
    const q = text.trim();
    if (!q || busy.value) return;

    messages.value.push({ from: 'me', text: q });
    question.value = '';
    busy.value = true;
    await scrollDown();

    try {
        const response = await fetch('/chatbot', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf() },
            body: JSON.stringify({ question: q }),
        });

        if (response.status === 429) {
            messages.value.push({ from: 'bot', text: t().busy });
        } else if (!response.ok) {
            throw new Error(String(response.status));
        } else {
            const data = await response.json();
            if (data.lang === 'tl' || data.lang === 'en') {
                lang.value = data.lang;
                savePreferredLanguage(data.lang);
            }
            messages.value.push({ from: 'bot', text: data.answer, suggestions: data.suggestions, guide: data.guide, lang: data.lang });
            // Asked how or where: close the chat and highlight the way. Otherwise offer a button.
            if (data.guide && data.teach) play(data.guide);
        }
    } catch {
        messages.value.push({ from: 'bot', text: t().error });
    } finally {
        busy.value = false;
        await scrollDown();
    }
}

function reset(): void {
    messages.value = [greeting()];
}

/** Show the steps on the page: the chat folds away so the highlighted part is visible. */
function play(guide: Guide): void {
    open.value = false;
    startTour({ steps: guide.steps, id: 'guide', lang: lang.value });
}
</script>

<template>
    <div class="fixed right-4 bottom-4 z-50 flex flex-col items-end gap-3 print:hidden sm:right-6 sm:bottom-6" @keydown.esc="open = false">
        <!-- Chat panel -->
        <section
            v-if="open"
            class="flex h-[min(32rem,calc(100vh-7rem))] w-[calc(100vw-2rem)] max-w-sm flex-col overflow-hidden border bg-background text-foreground shadow-2xl"
            aria-label="Help assistant"
        >
            <header class="flex items-center justify-between gap-2 bg-primary px-4 py-3 text-primary-foreground">
                <div class="flex items-center gap-2">
                    <Bot class="size-5" />
                    <div>
                        <div class="text-sm leading-tight font-semibold">EskwelaHub Assistant</div>
                        <div class="text-xs opacity-80">{{ t().sub }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <div class="flex border border-white/40 text-xs" role="group" aria-label="Language">
                        <button type="button" class="px-2 py-1" :class="lang === 'en' ? 'bg-white text-primary' : 'hover:bg-white/15'" :aria-pressed="lang === 'en'" @click="setLang('en')">EN</button>
                        <button type="button" class="px-2 py-1" :class="lang === 'tl' ? 'bg-white text-primary' : 'hover:bg-white/15'" :aria-pressed="lang === 'tl'" @click="setLang('tl')">TL</button>
                    </div>
                    <button type="button" class="px-2 py-1 text-xs hover:bg-white/15" @click="reset">{{ t().newChat }}</button>
                    <button type="button" class="p-1 hover:bg-white/15" aria-label="Close help" @click="open = false"><X class="size-4" /></button>
                </div>
            </header>

            <div ref="box" class="flex flex-1 flex-col gap-3 overflow-y-auto p-4" aria-live="polite">
                <div v-for="(m, i) in messages" :key="i" :class="m.from === 'me' ? 'self-end' : 'self-start'" class="max-w-[88%]">
                    <div
                        :class="m.from === 'me' ? 'bg-primary text-primary-foreground' : 'bg-muted'"
                        class="px-3 py-2 text-sm whitespace-pre-line"
                    >
                        {{ m.text }}
                    </div>
                    <button
                        v-if="m.guide"
                        type="button"
                        class="mt-2 border border-primary px-3 py-1 text-xs font-medium text-primary hover:bg-primary hover:text-primary-foreground"
                        @click="play(m.guide)"
                    >
                        {{ m.lang === 'tl' ? text.tl.show : text.en.show }}
                    </button>
                    <div v-if="m.suggestions?.length" class="mt-2 flex flex-wrap gap-1.5">
                        <button
                            v-for="s in m.suggestions"
                            :key="s.id"
                            type="button"
                            class="border px-3 py-1 text-left text-xs hover:bg-muted"
                            @click="ask(s.question)"
                        >
                            {{ s.question }}
                        </button>
                    </div>
                </div>

                <!-- Starter questions, only before the first question -->
                <div v-if="messages.length === 1" class="flex flex-wrap gap-1.5">
                    <button v-for="s in t().starters" :key="s" type="button" class="border px-3 py-1 text-left text-xs hover:bg-muted" @click="ask(s)">
                        {{ s }}
                    </button>
                </div>

                <div v-if="busy" class="self-start bg-muted px-3 py-2 text-sm text-muted-foreground" aria-label="The assistant is typing">...</div>
            </div>

            <form class="flex gap-2 border-t p-3" @submit.prevent="ask(question)">
                <input
                    ref="input"
                    v-model="question"
                    type="text"
                    maxlength="300"
                    :placeholder="t().placeholder"
                    class="h-9 min-w-0 flex-1 border border-input bg-transparent px-4 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <button
                    type="submit"
                    :disabled="busy || !question.trim()"
                    class="flex size-9 items-center justify-center bg-primary text-primary-foreground disabled:opacity-50"
                    aria-label="Send question"
                >
                    <Send class="size-4" />
                </button>
            </form>
            <p class="px-4 pb-3 text-[11px] text-muted-foreground">{{ t().privacy }}</p>
        </section>

        <!-- Floating round button -->
        <button
            type="button"
            class="flex size-14 items-center justify-center bg-primary text-primary-foreground shadow-lg transition hover:scale-105 focus-visible:ring-4 focus-visible:ring-ring/50 focus-visible:outline-none"
            data-tour="chat-button"
            :aria-label="open ? 'Close help assistant' : 'Open help assistant'"
            :aria-expanded="open"
            @click="toggle"
        >
            <X v-if="open" class="size-6" />
            <MessageCircle v-else class="size-6" />
        </button>
    </div>
</template>
