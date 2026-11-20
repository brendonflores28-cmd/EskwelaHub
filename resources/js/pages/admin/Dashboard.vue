<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { computed } from 'vue';
import BarChart from '@/components/BarChart.vue';
import BarList from '@/components/BarList.vue';
import Tip from '@/components/Tip.vue';
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
        forecast: { label: string; start_year: number; predicted: number }[];
        slope: number;
        r2: number;
        enough_data: boolean;
        note: string;
    };
};

const props = defineProps<{ metrics: Metrics; canExport: boolean }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] },
});

const k = computed(() => props.metrics.kpis);
const current = computed(() => props.metrics.enrollment.find((y) => y.is_current));
const short = (label: string) => `${label.slice(2, 4)}-${label.slice(7)}`; // "2025-2026" -> "25-26"

// The five numbers a principal looks at first. Each opens the page behind it.
const kpiCards = computed(() => [
    { label: 'Student records', value: k.value.students, href: '/staff/students', tip: 'All learner records, past and present. Click to search them.' },
    { label: 'Enrolled this year', value: k.value.enrolled_now, href: '/staff/sections', tip: `Learners enrolled in ${k.value.school_year ?? 'the current school year'}. Click to see the class sections.` },
    { label: 'Graduates on file', value: k.value.graduates, href: '/staff/students?status=graduated', tip: 'Learners marked as graduated. Click to list them.' },
    { label: 'Open requests', value: k.value.open_requests, href: '/staff/requests', tip: 'Document requests not finished yet. Click to process them.' },
    {
        label: 'Satisfaction (1 to 5)',
        value: props.metrics.satisfaction.responses ? props.metrics.satisfaction.overall : '-',
        href: '/admin/surveys',
        tip: 'Average rating from the surveys after each request or enrollment.',
    },
]);

/**
 * ONE enrollment chart: finished school years, this year, and the forecast for the next two years.
 * (The forecast for this year is in the bar's hover text, so no year is drawn twice.)
 */
const enrollmentItems = computed(() => {
    const finished = props.metrics.enrollment
        .filter((y) => !y.is_current && (!current.value || y.start_year < current.value.start_year))
        .slice(-10);
    const items: { label: string; value: number; kind: 'actual' | 'current' | 'forecast'; hint: string }[] = finished.map((y) => ({
        label: short(y.label),
        value: y.count,
        kind: 'actual',
        hint: `${y.label}: ${y.count} learners`,
    }));

    if (current.value) {
        const expected = props.metrics.forecast.forecast.find((f) => f.start_year === current.value!.start_year)?.predicted;
        items.push({
            label: short(current.value.label),
            value: current.value.count,
            kind: 'current',
            hint: `${current.value.label} (this year): ${current.value.count} enrolled so far${expected ? `, forecast ${expected}` : ''}`,
        });
    }

    for (const f of props.metrics.forecast.forecast.filter((f) => !current.value || f.start_year > current.value.start_year)) {
        items.push({ label: short(f.label), value: f.predicted, kind: 'forecast', hint: `${f.label}: about ${f.predicted} learners (forecast)` });
    }

    return items;
});

const graduateItems = computed(() =>
    props.metrics.graduates.slice(-10).map((g) => ({ label: short(g.label), value: g.count, kind: 'actual' as const, hint: `${g.label}: ${g.count} graduates` })),
);
const monthItems = computed(() =>
    props.metrics.requests.by_month.map((m) => ({ label: m.month.slice(5) + '/' + m.month.slice(2, 4), value: m.count, kind: 'actual' as const, hint: `${m.month}: ${m.count} requests` })),
);
const statusRows = computed(() => props.metrics.requests.by_status.filter((s) => s.count > 0).map((s) => ({ label: s.label, value: s.count })));
const strandRows = computed(() =>
    props.metrics.strands.map((s) => ({ label: s.label, value: s.total, hint: `${s.label}: Grade 11 ${s.grade11}, Grade 12 ${s.grade12}` })),
);

const s = computed(() => props.metrics.satisfaction);
const ratingRows = computed(() => [
    { label: 'Overall', value: s.value.overall },
    { label: 'Speed of service', value: s.value.speed },
    { label: 'Staff courtesy', value: s.value.staff },
]);
const sentimentRows = computed(() => [
    { label: 'Positive', value: s.value.sentiment.positive, tone: 'good' as const },
    { label: 'Neutral', value: s.value.sentiment.neutral, tone: 'neutral' as const },
    { label: 'Negative', value: s.value.sentiment.negative, tone: 'bad' as const },
]);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-5 p-4">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="text-2xl font-semibold">Principal dashboard</h1>
                <p class="text-sm text-muted-foreground">School year {{ k.school_year ?? '-' }}</p>
            </div>
            <Tip v-if="canExport" text="Download all these numbers as an Excel file">
                <Button as-child data-tour="export">
                    <a href="/admin/dashboard/export"><Download class="size-4" /> Export to Excel</a>
                </Button>
            </Tip>
        </div>

        <!-- The five numbers to read first -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5" data-tour="kpis">
            <Tip v-for="c in kpiCards" :key="c.label" :text="c.tip">
                <Link :href="c.href" class="block border bg-card p-4 transition hover:border-primary">
                    <div class="text-sm text-muted-foreground">{{ c.label }}</div>
                    <div class="mt-1 font-serif text-3xl font-bold">{{ c.value }}</div>
                </Link>
            </Tip>
        </div>

        <!-- One chart for enrollment: past years, this year and the forecast -->
        <Card data-tour="enrollment-chart">
            <CardHeader>
                <CardTitle>Enrollment per school year</CardTitle>
                <CardDescription class="flex flex-wrap items-center gap-x-4 gap-y-1">
                    <span class="flex items-center gap-1.5"><i class="inline-block size-3 bg-[color-mix(in_oklab,var(--primary)_62%,white)]"></i> Finished years</span>
                    <span class="flex items-center gap-1.5"><i class="inline-block size-3 bg-primary"></i> This year</span>
                    <span v-if="metrics.forecast.enough_data" class="flex items-center gap-1.5"><i class="inline-block size-3 border border-dashed border-primary bg-primary/25"></i> Forecast</span>
                </CardDescription>
            </CardHeader>
            <CardContent>
                <BarChart :items="enrollmentItems" />
                <p v-if="metrics.forecast.enough_data" class="mt-3 text-xs text-muted-foreground">
                    Trend {{ metrics.forecast.slope >= 0 ? '+' : '' }}{{ metrics.forecast.slope }} learners a year. {{ metrics.forecast.note }}
                </p>
                <p v-else class="mt-3 text-xs text-muted-foreground">{{ metrics.forecast.note }}</p>
            </CardContent>
        </Card>

        <div class="grid gap-5 lg:grid-cols-2">
            <Card>
                <CardHeader><CardTitle>Graduates per school year</CardTitle></CardHeader>
                <CardContent>
                    <BarChart v-if="graduateItems.length" :items="graduateItems" />
                    <p v-else class="text-sm text-muted-foreground">No graduates recorded yet.</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Document requests per month</CardTitle></CardHeader>
                <CardContent><BarChart :items="monthItems" :height="170" /></CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Requests by status</CardTitle></CardHeader>
                <CardContent>
                    <BarList v-if="statusRows.length" :rows="statusRows" />
                    <p v-else class="text-sm text-muted-foreground">No requests yet.</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Senior high by strand</CardTitle></CardHeader>
                <CardContent>
                    <BarList v-if="strandRows.length" :rows="strandRows" />
                    <p v-else class="text-sm text-muted-foreground">No senior high learners yet.</p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Client satisfaction</CardTitle>
                <CardDescription>{{ s.responses }} survey {{ s.responses === 1 ? 'answer' : 'answers' }}</CardDescription>
            </CardHeader>
            <CardContent v-if="s.responses" class="grid gap-6 md:grid-cols-2">
                <div>
                    <h3 class="mb-2 text-sm font-medium">Ratings (out of 5)</h3>
                    <BarList :rows="ratingRows" :max="5" />
                </div>
                <div>
                    <h3 class="mb-2 text-sm font-medium">Comments</h3>
                    <BarList :rows="sentimentRows" />
                </div>
            </CardContent>
            <CardContent v-else class="text-sm text-muted-foreground">No survey answers yet.</CardContent>
        </Card>
    </div>
</template>
