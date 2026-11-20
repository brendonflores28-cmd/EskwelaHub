<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import NativeSelect from '@/components/NativeSelect.vue';
import SimplePagination from '@/components/SimplePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type Row = {
    id: number;
    when: string;
    about: string | null;
    overall: number;
    speed: number;
    staff: number;
    comment: string | null;
    sentiment: string | null;
};

const props = defineProps<{
    surveys: { data: Row[]; prev_page_url: string | null; next_page_url: string | null; total: number; from: number | null; to: number | null; current_page: number; last_page: number };
    filters: { sentiment: string | null };
    summary: { total: number; average: number; positive: number; neutral: number; negative: number };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Satisfaction surveys', href: '/admin/surveys' }] },
});

const sentiment = ref<string | number | null>(props.filters.sentiment ?? '');
watch(sentiment, () => router.get('/admin/surveys', { sentiment: sentiment.value || undefined }, { preserveState: true, replace: true }));

const options = ['positive', 'neutral', 'negative'].map((s) => ({ value: s, label: s }));
const color: Record<string, string> = { positive: 'default', negative: 'destructive', neutral: 'secondary' };
</script>

<template>
    <Head title="Satisfaction surveys" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-semibold">Client satisfaction</h1>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <Card><CardHeader><CardDescription>Surveys</CardDescription><CardTitle class="text-3xl">{{ summary.total }}</CardTitle></CardHeader></Card>
            <Card><CardHeader><CardDescription>Average (1 to 5)</CardDescription><CardTitle class="text-3xl">{{ summary.average }}</CardTitle></CardHeader></Card>
            <Card><CardHeader><CardDescription>Positive comments</CardDescription><CardTitle class="text-3xl">{{ summary.positive }}</CardTitle></CardHeader></Card>
            <Card><CardHeader><CardDescription>Neutral comments</CardDescription><CardTitle class="text-3xl">{{ summary.neutral }}</CardTitle></CardHeader></Card>
            <Card><CardHeader><CardDescription>Negative comments</CardDescription><CardTitle class="text-3xl">{{ summary.negative }}</CardTitle></CardHeader></Card>
        </div>

        <div class="max-w-xs"><NativeSelect v-model="sentiment" :options="options" placeholder="All comments" /></div>

        <div class="overflow-x-auto border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">When</th>
                        <th class="p-3">About</th>
                        <th class="p-3">Overall / speed / staff</th>
                        <th class="p-3">Comment</th>
                        <th class="p-3">Sentiment</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in surveys.data" :key="s.id" class="border-t align-top">
                        <td class="p-3 whitespace-nowrap">{{ s.when }}</td>
                        <td class="p-3">{{ s.about }}</td>
                        <td class="p-3">{{ s.overall }} / {{ s.speed }} / {{ s.staff }}</td>
                        <td class="p-3">{{ s.comment ?? '-' }}</td>
                        <td class="p-3"><Badge v-if="s.sentiment" :variant="color[s.sentiment] as 'default'" class="capitalize">{{ s.sentiment }}</Badge></td>
                    </tr>
                    <tr v-if="surveys.data.length === 0">
                        <td colspan="5" class="p-6 text-center text-muted-foreground">No surveys yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <SimplePagination :page="surveys" />
    </div>
</template>
