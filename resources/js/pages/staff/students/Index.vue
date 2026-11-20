<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref, watch } from 'vue';
import NativeSelect from '@/components/NativeSelect.vue';
import SimplePagination from '@/components/SimplePagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Enrollment, Option, Paginated, SchoolYearOption, Student } from '@/types/records';

type Filters = {
    q: string;
    school_year_id: number | null;
    grade_level: number | null;
    status: string | null;
};

const props = defineProps<{
    students: Paginated<Student>;
    filters: Filters;
    schoolYears: SchoolYearOption[];
    statuses: Option[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Student records', href: '/staff/students' }] },
});

const q = ref(props.filters.q ?? '');
const schoolYear = ref<string | number | null>(props.filters.school_year_id ?? '');
const grade = ref<string | number | null>(props.filters.grade_level ?? '');
const status = ref<string | number | null>(props.filters.status ?? '');

/** Send the filters to the server. Empty values are left out of the URL. */
function search(): void {
    router.get(
        '/staff/students',
        {
            q: q.value || undefined,
            school_year_id: schoolYear.value || undefined,
            grade_level: grade.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

let timer: ReturnType<typeof setTimeout>;
watch(q, () => {
    clearTimeout(timer);
    timer = setTimeout(search, 350); // wait until the user stops typing
});
watch([schoolYear, grade, status], search);

/** "2026-2027, Grade 11 STEM - STEM-A": one string, so the spaces are always right. */
function enrollmentText(e: Enrollment): string {
    const grade = `Grade ${e.grade_level}${e.strand_short ? ' ' + e.strand_short : ''}`;

    return [e.school_year, e.section ? `${grade} - ${e.section}` : grade].filter(Boolean).join(', ');
}

const grades = [7, 8, 9, 10, 11, 12].map((g) => ({ value: g, label: `Grade ${g}` }));
const yearOptions = props.schoolYears.map((y) => ({ value: y.id, label: y.label }));
</script>

<template>
    <Head title="Student records" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Student records</h1>
            <Button as-child title="Add a student record by hand">
                <Link href="/staff/students/create"><Plus class="size-4" /> New record</Link>
            </Button>
        </div>

        <div class="grid gap-2 md:grid-cols-4">
            <Input data-tour="student-search" v-model="q" placeholder="LRN or name (typos are OK)" autofocus />
            <NativeSelect v-model="schoolYear" :options="yearOptions" placeholder="All school years" />
            <NativeSelect v-model="grade" :options="grades" placeholder="All grades" />
            <NativeSelect v-model="status" :options="statuses" placeholder="All statuses" />
        </div>

        <div class="overflow-x-auto border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Name</th>
                        <th class="p-3">LRN</th>
                        <th class="p-3">Birthdate</th>
                        <th class="p-3">Latest enrollment</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in students.data" :key="s.id" class="border-t hover:bg-muted/30">
                        <td class="p-3 font-medium">
                            <Link :href="`/staff/students/${s.id}`" class="hover:underline">{{ s.full_name }}</Link>
                        </td>
                        <td class="p-3">{{ s.lrn ?? 'No LRN' }}</td>
                        <td class="p-3">{{ s.birthdate }}</td>
                        <td class="p-3">
                            <span v-if="s.latest_enrollment">{{ enrollmentText(s.latest_enrollment) }}</span>
                            <span v-else class="text-muted-foreground">None</span>
                        </td>
                        <td class="p-3"><StatusBadge :status="s.status" /></td>
                    </tr>
                    <tr v-if="students.data.length === 0">
                        <td colspan="5" class="p-6 text-center text-muted-foreground">No matching records.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <SimplePagination :page="students" />
    </div>
</template>
