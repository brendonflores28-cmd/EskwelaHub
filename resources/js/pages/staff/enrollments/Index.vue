<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import NativeSelect from '@/components/NativeSelect.vue';
import SimplePagination from '@/components/SimplePagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { EnrollmentForm, Option, Paginated } from '@/types/records';

const props = defineProps<{
    applications: Paginated<EnrollmentForm>;
    filters: { status: string | null };
    statuses: Option[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Enrollment forms', href: '/staff/enrollments' }] },
});

const status = ref<string | number | null>(props.filters.status ?? '');
watch(status, () => router.get('/staff/enrollments', { status: status.value || undefined }, { preserveState: true, replace: true }));
</script>

<template>
    <Head title="Enrollment forms" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-semibold">Enrollment forms</h1>

        <div class="max-w-xs"><NativeSelect v-model="status" :options="statuses" placeholder="All statuses" /></div>

        <div class="overflow-x-auto border">
            <table data-tour="enrollments-table" class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Reference</th>
                        <th class="p-3">Learner</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Applicant</th>
                        <th class="p-3">Sent</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in applications.data" :key="a.id" class="border-t hover:bg-muted/30">
                        <td class="p-3 font-medium">
                            <Link :href="`/staff/enrollments/${a.id}`" class="hover:underline">{{ a.reference_no }}</Link>
                        </td>
                        <td class="p-3">{{ a.full_name }}</td>
                        <td class="p-3">{{ a.type_label }}, Grade {{ a.grade_level }}</td>
                        <td class="p-3">{{ a.applicant?.name }}</td>
                        <td class="p-3">{{ a.created_at }}</td>
                        <td class="p-3"><StatusBadge :status="a.status" /></td>
                    </tr>
                    <tr v-if="applications.data.length === 0">
                        <td colspan="6" class="p-6 text-center text-muted-foreground">No enrollment forms.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <SimplePagination :page="applications" />
    </div>
</template>
