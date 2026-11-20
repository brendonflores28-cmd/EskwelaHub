<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Student } from '@/types/records';

const props = defineProps<{ student: { data: Student } }>();
const s = computed(() => props.student.data);

defineOptions({
    layout: { breadcrumbs: [{ title: 'My records', href: '/my-records' }] },
});
</script>

<template>
    <Head :title="s.full_name" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ s.full_name }}</h1>
            <p class="text-sm text-muted-foreground">
                LRN {{ s.lrn ?? 'not assigned' }} - born {{ s.birthdate }}
                <StatusBadge :status="s.status" class="ml-2" />
            </p>
        </div>

        <Card>
            <CardHeader><CardTitle>Enrollment history</CardTitle></CardHeader>
            <CardContent>
                <table class="w-full text-sm">
                    <thead class="text-left text-muted-foreground">
                        <tr>
                            <th class="py-2">School year</th>
                            <th>Grade</th>
                            <th>Section</th>
                            <th>Average</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in s.enrollments" :key="e.id" class="border-t">
                            <td class="py-2">{{ e.school_year }}</td>
                            <td>{{ e.grade_level }}<span v-if="e.strand"> - {{ e.strand_short }}</span></td>
                            <td>{{ e.section ?? '-' }}</td>
                            <td>{{ e.general_average ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>

        <Card v-if="s.graduation">
            <CardHeader><CardTitle>Graduation</CardTitle></CardHeader>
            <CardContent class="text-sm">
                School year {{ s.graduation.school_year }}
                <span v-if="s.graduation.date_graduated">, graduated {{ s.graduation.date_graduated }}</span>
                <span v-if="s.graduation.honors"> - {{ s.graduation.honors }}</span>
            </CardContent>
        </Card>
    </div>
</template>
