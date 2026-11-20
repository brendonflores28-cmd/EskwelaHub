<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Faq = { id: number; category: string; question: string; answer: string };
type Message = { from: 'me' | 'bot'; text: string; suggestions?: { id: number; question: string }[] };

const props = defineProps<{ faqs: Faq[] }>();

const messages = ref<Message[]>([
    { from: 'bot', text: 'Hello! Ask me about enrollment or requesting school documents. You can write in English or Tagalog.' },
]);
const question = ref('');
const busy = ref(false);
const box = ref<HTMLElement | null>(null);

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

    try {
        const response = await fetch('/chatbot', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': xsrf() },
            body: JSON.stringify({ question: q }),
        });
        if (!response.ok) throw new Error(String(response.status));
        const data = await response.json();
        messages.value.push({ from: 'bot', text: data.answer, suggestions: data.suggestions });
    } catch {
        messages.value.push({ from: 'bot', text: 'Sorry, I cannot answer right now. Please try again in a minute.' });
    } finally {
        busy.value = false;
        await nextTick();
        box.value?.scrollTo({ top: box.value.scrollHeight });
    }
}

/** A suggestion is a known question: show its answer without another request. */
function pickSuggestion(s: { id: number; question: string }): void {
    const faq = props.faqs.find((f) => f.id === s.id);
    messages.value.push({ from: 'me', text: s.question });
    messages.value.push({ from: 'bot', text: faq?.answer ?? 'Please ask the registrar.' });
}
</script>

<template>
    <Head title="Help" />

    <main class="mx-auto flex min-h-screen max-w-3xl flex-col gap-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Help: enrollment and documents</h1>
            <Button variant="outline" size="sm" as-child><Link href="/">Home</Link></Button>
        </div>

        <section class="flex flex-col rounded-xl border">
            <div ref="box" class="flex max-h-96 min-h-60 flex-col gap-3 overflow-y-auto p-4">
                <div v-for="(m, i) in messages" :key="i" :class="m.from === 'me' ? 'self-end' : 'self-start'" class="max-w-[85%]">
                    <div :class="m.from === 'me' ? 'bg-primary text-primary-foreground' : 'bg-muted'" class="rounded-lg px-3 py-2 text-sm">
                        {{ m.text }}
                    </div>
                    <div v-if="m.suggestions?.length" class="mt-2 flex flex-wrap gap-2">
                        <Button v-for="s in m.suggestions" :key="s.id" size="sm" variant="outline" @click="pickSuggestion(s)">
                            {{ s.question }}
                        </Button>
                    </div>
                </div>
                <div v-if="busy" class="text-sm text-muted-foreground">...</div>
            </div>
            <form class="flex gap-2 border-t p-3" @submit.prevent="ask(question)">
                <Input v-model="question" placeholder="Type your question, for example: how to request Form 137?" maxlength="300" />
                <Button type="submit" :disabled="busy">Ask</Button>
            </form>
        </section>
        <p class="text-xs text-muted-foreground">Please do not type names, LRNs or other personal details here.</p>

        <section>
            <h2 class="mb-2 text-lg font-medium">Frequently asked questions</h2>
            <details v-for="f in faqs" :key="f.id" class="border-b py-2">
                <summary class="cursor-pointer font-medium">{{ f.question }}</summary>
                <p class="mt-1 text-sm text-muted-foreground">{{ f.answer }}</p>
            </details>
        </section>
    </main>
</template>
