<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ScanSearch } from '@lucide/vue';
import SimplePagination from '@/components/SimplePagination.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Paginated, Student } from '@/types/records';

type Pair = { id: number; score: number; reason: string; a: Student; b: Student };

defineProps<{
    duplicates: Pair[];
    counts: Record<string, number>;
    labels: Record<string, string>;
    issue: string | null;
    incomplete: Paginated<Student> | null;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Record alerts', href: '/staff/alerts' }] },
});

function merge(flagId: number, keep: number, other: string): void {
    if (confirm(`Merge the two records and keep ${other}? The other record is archived and its history moves to the kept one.`)) {
        router.post(`/staff/alerts/${flagId}/merge`, { keep });
    }
}
</script>

<template>
    <Head title="Record alerts" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h1 class="text-2xl font-semibold">Record alerts</h1>
                <p class="text-sm text-muted-foreground">Possible duplicates and incomplete records. The check runs every night.</p>
            </div>
            <Button variant="outline" @click="router.post('/staff/alerts/scan')"><ScanSearch class="size-4" /> Check now</Button>
        </div>

        <Card>
            <CardHeader><CardTitle>Possible duplicates ({{ duplicates.length }})</CardTitle></CardHeader>
            <CardContent class="space-y-3">
                <div v-for="d in duplicates" :key="d.id" class="border p-3 text-sm">
                    <div class="mb-2 text-muted-foreground">{{ d.reason }} - match {{ Math.round(d.score * 100) }}%</div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div v-for="(s, i) in [d.a, d.b]" :key="s.id" class="border p-2">
                            <Link :href="`/staff/students/${s.id}`" class="font-medium underline" target="_blank">{{ s.full_name }}</Link>
                            <div>Born {{ s.birthdate }}, LRN {{ s.lrn ?? 'none' }}</div>
                            <div class="text-muted-foreground">
                                {{ s.latest_enrollment ? `${s.latest_enrollment.school_year}, Grade ${s.latest_enrollment.grade_level}` : 'No enrollment' }}
                            </div>
                            <Button size="sm" class="mt-2" @click="merge(d.id, s.id, i === 0 ? 'the left record' : 'the right record')">
                                Keep this one and merge
                            </Button>
                        </div>
                    </div>
                    <Button size="sm" variant="outline" class="mt-2" @click="router.post(`/staff/alerts/${d.id}/dismiss`, {}, { preserveScroll: true })">
                        They are different people
                    </Button>
                </div>
                <p v-if="duplicates.length === 0" class="text-sm text-muted-foreground">No possible duplicates.</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader><CardTitle>Incomplete records</CardTitle></CardHeader>
            <CardContent class="space-y-3">
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="(label, key) in labels"
                        :key="key"
                        size="sm"
                        :variant="issue === key ? 'default' : 'outline'"
                        @click="router.get('/staff/alerts', { issue: key }, { preserveScroll: true })"
                    >
                        {{ label }} ({{ counts[key] }})
                    </Button>
                </div>

                <table v-if="incomplete" class="w-full text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr><th class="py-2">Name</th><th>LRN</th><th>Birthdate</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in incomplete.data" :key="s.id" class="border-t">
                            <td class="py-2">
                                <Link :href="`/staff/students/${s.id}/edit`" class="underline">{{ s.full_name }}</Link>
                            </td>
                            <td>{{ s.lrn ?? '-' }}</td>
                            <td>{{ s.birthdate }}</td>
                            <td class="capitalize">{{ s.status }}</td>
                        </tr>
                    </tbody>
                </table>
                <SimplePagination v-if="incomplete" :page="incomplete" />
            </CardContent>
        </Card>
    </div>
</template>
