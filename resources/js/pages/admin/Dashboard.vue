<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { computed } from 'vue';
import BarChart from '@/components/BarChart.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

type Metrics = {
    generated_at: string;
    kpis: {
        school_year: string | null;
        students: number;
        enrolled_now: number;
        graduates: number;
        open_requests: number;
        avg_processing_days: number | null;
    };
    enrollment: { label: string; start_year: number; count: number; is_current: boolean }[];
    graduates: { label: string; count: number }[];
    strands: { strand: string; label: string; grade11: number; grade12: number; total: number }[];
    requests: {
        by_status: { status: string; label: string; count: number }[];
        by_month: { month: string; count: number }[];
    };
    satisfaction: {
        responses: number;
        overall: number;
        speed: number;
        staff: number;
        sentiment: { positive: number; neutral: number; negative: number };
    };
    forecast: {
        history: { label: string; count: number }[];
        forecast: { label: string; predicted: number }[];
        slope: number;
        r2: number;
        enough_data: boolean;
        note: string;
    };
};

const props = defineProps<{ metrics: Metrics; canExport: boolean }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Principal dashboard', href: dashboard() }] },
});

const k = computed(() => props.metrics.kpis);

const kpiCards = computed(() => [
    { label: 'Student records', value: k.value.students },
    { label: 'Enrolled this school year', value: k.value.enrolled_now },
    { label: 'Graduates on file', value: k.value.graduates },
    { label: 'Open document requests', value: k.value.open_requests },
    { label: 'Average days to release', value: k.value.avg_processing_days ?? '-' },
    { label: 'Survey average (1 to 5)', value: props.metrics.satisfaction.responses ? props.metrics.satisfaction.overall : '-' },
]);

const enrollmentItems = computed(() =>
    props.metrics.enrollment.map((e) => ({ label: e.label.slice(2), value: e.count, highlight: e.is_current })),
);
const strandItems = computed(() => props.metrics.strands.map((s) => ({ label: s.label, value: s.total })));
const graduateItems = computed(() => props.metrics.graduates.map((g) => ({ label: g.label.slice(2), value: g.count })));
const statusItems = computed(() => props.metrics.requests.by_status.map((s) => ({ label: s.label, value: s.count })));
const monthItems = computed(() => props.metrics.requests.by_month.map((m) => ({ label: m.month.slice(2), value: m.count })));
const forecastItems = computed(() => [
    ...props.metrics.forecast.history.map((h) => ({ label: h.label.slice(2), value: h.count })),
    ...props.metrics.forecast.forecast.map((f) => ({ label: f.label.slice(2), value: f.predicted, projected: true })),
]);

const sentiment = computed(() => props.metrics.satisfaction.sentiment);
const sentimentTotal = computed(() => Math.max(1, sentiment.value.positive + sentiment.value.neutral + sentiment.value.negative));
const pct = (n: number) => Math.round((n / sentimentTotal.value) * 100);
</script>

<template>
    <Head title="Principal dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h1 class="text-2xl font-semibold">Principal dashboard</h1>
                <p class="text-sm text-muted-foreground">School year {{ k.school_year ?? '-' }} - updated {{ metrics.generated_at }}</p>
            </div>
            <Button v-if="canExport" as-child>
                <a href="/admin/dashboard/export"><Download class="size-4" /> Export to Excel</a>
            </Button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <Card v-for="c in kpiCards" :key="c.label">
                <CardHeader>
                    <CardDescription>{{ c.label }}</CardDescription>
                    <CardTitle class="text-3xl">{{ c.value }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Enrollment forecast</CardTitle>
                <CardDescription>Dashed bars are the forecast.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
                <BarChart v-if="metrics.forecast.enough_data" :items="forecastItems" />
                <p class="text-sm text-muted-foreground">{{ metrics.forecast.note }}</p>
                <p v-if="metrics.forecast.enough_data" class="text-sm">
                    Trend: {{ metrics.forecast.slope >= 0 ? '+' : '' }}{{ metrics.forecast.slope }} learners per year
                    (fit quality R&sup2; = {{ metrics.forecast.r2 }}; closer to 1 means the line follows the past well).
                </p>
            </CardContent>
        </Card>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Enrollment per school year</CardTitle>
                </CardHeader>
                <CardContent><BarChart :items="enrollmentItems" /></CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Graduates per school year</CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart v-if="graduateItems.length" :items="graduateItems" />
                    <p v-else class="text-sm text-muted-foreground">No graduation data yet.</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Senior high by strand</CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart v-if="strandItems.length" :items="strandItems" />
                    <p v-else class="text-sm text-muted-foreground">No senior high learners with a strand yet.</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Document requests by status</CardTitle></CardHeader>
                <CardContent><BarChart :items="statusItems" /></CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Document requests per month (last 12 months)</CardTitle>
                </CardHeader>
                <CardContent><BarChart :items="monthItems" /></CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Client satisfaction</CardTitle>
                <CardDescription>{{ metrics.satisfaction.responses }} survey responses.</CardDescription>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2">
                <div class="space-y-1 text-sm">
                    <div>Overall: <strong>{{ metrics.satisfaction.overall }}</strong> / 5</div>
                    <div>Speed of service: <strong>{{ metrics.satisfaction.speed }}</strong> / 5</div>
                    <div>Staff courtesy: <strong>{{ metrics.satisfaction.staff }}</strong> / 5</div>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="text-muted-foreground">What people wrote (sentiment of comments)</div>
                    <div class="flex h-4 overflow-hidden bg-muted" role="img" :aria-label="`Positive ${pct(sentiment.positive)}%, neutral ${pct(sentiment.neutral)}%, negative ${pct(sentiment.negative)}%`">
                        <div class="bg-green-600" :style="{ width: pct(sentiment.positive) + '%' }"></div>
                        <div class="bg-gray-400" :style="{ width: pct(sentiment.neutral) + '%' }"></div>
                        <div class="bg-red-600" :style="{ width: pct(sentiment.negative) + '%' }"></div>
                    </div>
                    <div class="flex gap-4">
                        <span>Positive {{ sentiment.positive }}</span>
                        <span>Neutral {{ sentiment.neutral }}</span>
                        <span>Negative {{ sentiment.negative }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
