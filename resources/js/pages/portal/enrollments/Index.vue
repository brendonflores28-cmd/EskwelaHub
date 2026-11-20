<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import SimplePagination from '@/components/SimplePagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import type { EnrollmentForm, Paginated } from '@/types/records';

defineProps<{ applications: Paginated<EnrollmentForm> }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'My enrollment', href: '/my-enrollments' }] },
});
</script>

<template>
    <Head title="My enrollment" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">Online enrollment</h1>
            <Button as-child title="Enroll a learner online">
                <Link href="/my-enrollments/create" data-tour="enroll-new-btn"><Plus class="size-4" /> New enrollment form</Link>
            </Button>
        </div>

        <div class="overflow-x-auto border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Reference</th>
                        <th class="p-3">Learner</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Sent</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in applications.data" :key="a.id" class="border-t hover:bg-muted/30">
                        <td class="p-3 font-medium">
                            <Link :href="`/my-enrollments/${a.id}`" class="hover:underline">{{ a.reference_no }}</Link>
                        </td>
                        <td class="p-3">{{ a.full_name }}</td>
                        <td class="p-3">{{ a.type_label }} (Grade {{ a.grade_level }})</td>
                        <td class="p-3">{{ a.created_at }}</td>
                        <td class="p-3"><StatusBadge :status="a.status" /></td>
                    </tr>
                    <tr v-if="applications.data.length === 0">
                        <td colspan="5" class="p-6 text-center text-muted-foreground">No enrollment forms yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <SimplePagination :page="applications" />
    </div>
</template>
