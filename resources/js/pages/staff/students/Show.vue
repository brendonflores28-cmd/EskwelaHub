<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Option, SchoolYearOption, StrandOption, Student } from '@/types/records';

const props = defineProps<{
    student: { data: Student };
    schoolYears: SchoolYearOption[];
    statuses: Option[];
    curricula: Option[];
    strands: StrandOption[];
}>();

const s = computed(() => props.student.data);

defineOptions({
    layout: { breadcrumbs: [{ title: 'Student records', href: '/staff/students' }] },
});

const yearOptions = props.schoolYears.map((y) => ({ value: y.id, label: y.label }));
const grades = [7, 8, 9, 10, 11, 12].map((g) => ({ value: g, label: `Grade ${g}` }));

// Add or update the enrollment of one school year.
const enrollment = useForm({
    school_year_id: props.schoolYears[0]?.id ?? '',
    grade_level: 7,
    strand: '' as string,
    section: '',
    curriculum: '',
    status: 'enrolled',
    general_average: '',
});

// A strand is asked only for senior high (Grades 11 and 12).
const seniorHigh = computed(() => Number(enrollment.grade_level) >= 11);

function saveEnrollment(): void {
    if (!seniorHigh.value) enrollment.strand = '';
    enrollment.post(`/staff/students/${s.value.id}/enrollments`, { preserveScroll: true });
}

function removeEnrollment(id: number): void {
    if (confirm('Remove this enrollment?')) {
        router.delete(`/staff/students/${s.value.id}/enrollments/${id}`, { preserveScroll: true });
    }
}

// Graduation data (one record per student).
const graduation = useForm({
    school_year_id: s.value.graduation?.school_year_id ?? props.schoolYears[0]?.id ?? '',
    date_graduated: s.value.graduation?.date_graduated ?? '',
    honors: s.value.graduation?.honors ?? '',
    diploma_no: s.value.graduation?.diploma_no ?? '',
    general_average: s.value.graduation?.general_average ?? '',
});

function saveGraduation(): void {
    graduation.put(`/staff/students/${s.value.id}/graduation`, { preserveScroll: true });
}

function archive(): void {
    if (confirm('Archive this record? It will no longer appear in searches.')) {
        router.delete(`/staff/students/${s.value.id}`);
    }
}
</script>

<template>
    <Head :title="s.full_name" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h1 class="text-2xl font-semibold">{{ s.full_name }}</h1>
                <p class="text-sm text-muted-foreground">
                    LRN {{ s.lrn ?? 'not assigned' }} - born {{ s.birthdate }} - {{ s.sex === 'M' ? 'Male' : 'Female' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <StatusBadge :status="s.status" />
                <Button variant="outline" size="sm" as-child>
                    <Link :href="`/staff/students/${s.id}/edit`"><Pencil class="size-4" /> Edit</Link>
                </Button>
                <Button variant="destructive" size="sm" @click="archive"><Trash2 class="size-4" /> Archive</Button>
            </div>
        </div>

        <Card>
            <CardHeader><CardTitle>Enrollment history</CardTitle></CardHeader>
            <CardContent class="space-y-4">
                <div class="overflow-x-auto rounded-lg border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-left">
                            <tr>
                                <th class="p-2">School year</th>
                                <th class="p-2">Grade</th>
                                <th class="p-2">Section</th>
                                <th class="p-2">Curriculum</th>
                                <th class="p-2">Average</th>
                                <th class="p-2">Status</th>
                                <th class="p-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="e in s.enrollments" :key="e.id" class="border-t">
                                <td class="p-2">{{ e.school_year }}</td>
                                <td class="p-2">{{ e.grade_level }}<span v-if="e.strand"> - {{ e.strand_short }}</span></td>
                                <td class="p-2">{{ e.section ?? '-' }}</td>
                                <td class="p-2 uppercase">{{ e.curriculum }}</td>
                                <td class="p-2">{{ e.general_average ?? '-' }}</td>
                                <td class="p-2"><StatusBadge :status="e.status" /></td>
                                <td class="p-2 text-right">
                                    <Button variant="ghost" size="sm" @click="removeEnrollment(e.id)">Remove</Button>
                                </td>
                            </tr>
                            <tr v-if="!s.enrollments?.length">
                                <td colspan="7" class="p-4 text-center text-muted-foreground">No enrollments yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <form class="grid gap-3 md:grid-cols-6" @submit.prevent="saveEnrollment">
                    <div class="grid gap-1 md:col-span-2">
                        <Label>School year</Label>
                        <NativeSelect v-model="enrollment.school_year_id" :options="yearOptions" />
                        <InputError :message="enrollment.errors.school_year_id" />
                    </div>
                    <div class="grid gap-1">
                        <Label>Grade</Label>
                        <NativeSelect v-model="enrollment.grade_level" :options="grades" />
                    </div>
                    <div v-if="seniorHigh" class="grid gap-1">
                        <Label>Strand</Label>
                        <NativeSelect v-model="enrollment.strand" :options="strands.map((s) => ({ value: s.value, label: s.short }))" placeholder="Choose" />
                        <InputError :message="enrollment.errors.strand" />
                    </div>
                    <div class="grid gap-1">
                        <Label>Section</Label>
                        <Input v-model="enrollment.section" />
                    </div>
                    <div class="grid gap-1">
                        <Label>Curriculum</Label>
                        <NativeSelect v-model="enrollment.curriculum" :options="curricula" placeholder="Automatic" />
                    </div>
                    <div class="grid gap-1">
                        <Label>Average</Label>
                        <Input v-model="enrollment.general_average" inputmode="decimal" />
                        <InputError :message="enrollment.errors.general_average" />
                    </div>
                    <div class="md:col-span-6">
                        <Button type="submit" size="sm" :disabled="enrollment.processing">
                            Save enrollment for this school year
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader><CardTitle>Graduation</CardTitle></CardHeader>
            <CardContent>
                <form class="grid gap-3 md:grid-cols-5" @submit.prevent="saveGraduation">
                    <div class="grid gap-1">
                        <Label>School year</Label>
                        <NativeSelect v-model="graduation.school_year_id" :options="yearOptions" />
                    </div>
                    <div class="grid gap-1">
                        <Label>Date graduated</Label>
                        <Input v-model="graduation.date_graduated" type="date" />
                    </div>
                    <div class="grid gap-1">
                        <Label>Honors</Label>
                        <Input v-model="graduation.honors" />
                    </div>
                    <div class="grid gap-1">
                        <Label>Diploma no.</Label>
                        <Input v-model="graduation.diploma_no" />
                        <InputError :message="graduation.errors.diploma_no" />
                    </div>
                    <div class="grid gap-1">
                        <Label>Average</Label>
                        <Input v-model="graduation.general_average" inputmode="decimal" />
                    </div>
                    <div class="md:col-span-5">
                        <Button type="submit" size="sm" :disabled="graduation.processing">Save graduation data</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
