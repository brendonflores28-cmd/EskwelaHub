<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import EnrollmentDetails from '@/components/EnrollmentDetails.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { EnrollmentForm } from '@/types/records';

const props = defineProps<{ application: { data: EnrollmentForm } }>();
const a = computed(() => props.application.data);

defineOptions({
    layout: { breadcrumbs: [{ title: 'My enrollment', href: '/my-enrollments' }] },
});
</script>

<template>
    <Head :title="a.reference_no" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h1 class="text-2xl font-semibold">{{ a.reference_no }}</h1>
                <p class="text-sm text-muted-foreground">School year {{ a.school_year }} - sent {{ a.created_at }}</p>
            </div>
            <StatusBadge :status="a.status" />
        </div>

        <Card v-if="a.decision_note">
            <CardHeader><CardTitle>Note from the registrar</CardTitle></CardHeader>
            <CardContent class="text-sm">{{ a.decision_note }}</CardContent>
        </Card>

        <Card v-if="a.status === 'approved'">
            <CardHeader><CardTitle>Enrolled</CardTitle></CardHeader>
            <CardContent class="space-y-2 text-sm">
                <p>The learner is now on the school list and linked to your account.</p>
                <Button as-child><Link href="/my-records">Open my records</Link></Button>
            </CardContent>
        </Card>

        <EnrollmentDetails :a="a" />
    </div>
</template>
