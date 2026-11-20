<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import EnrollmentDetails from '@/components/EnrollmentDetails.vue';
import InputError from '@/components/InputError.vue';
import SimilarRecords from '@/components/SimilarRecords.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { EnrollmentForm } from '@/types/records';

const props = defineProps<{
    application: { data: EnrollmentForm };
    open: boolean;
    similar: { id: number; full_name: string; lrn: string | null; birthdate: string }[];
}>();
const a = computed(() => props.application.data);
const base = computed(() => `/staff/enrollments/${a.value.id}`);

defineOptions({
    layout: { breadcrumbs: [{ title: 'Enrollment forms', href: '/staff/enrollments' }] },
});

const decision = useForm({ note: '' });
</script>

<template>
    <Head :title="a.reference_no" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h1 class="text-2xl font-semibold">{{ a.reference_no }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ a.applicant?.name }} ({{ a.applicant?.email }}) - school year {{ a.school_year }}
                </p>
            </div>
            <StatusBadge :status="a.status" />
        </div>

        <SimilarRecords :similar="similar" />

        <Card v-if="a.student_id">
            <CardContent class="pt-6 text-sm">
                Student record created or linked:
                <Link :href="`/staff/students/${a.student_id}`" class="underline">open the record</Link>
            </CardContent>
        </Card>

        <Card v-if="open">
            <CardHeader><CardTitle>Decision</CardTitle></CardHeader>
            <CardContent class="space-y-3">
                <div class="grid max-w-xl gap-2">
                    <Label for="note">Note to the applicant (required to reject)</Label>
                    <Input id="note" v-model="decision.note" maxlength="500" />
                    <InputError :message="decision.errors.note" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button v-if="a.status === 'submitted'" variant="outline" @click="router.post(`${base}/review`, {}, { preserveScroll: true })">
                        Start review
                    </Button>
                    <Button :disabled="decision.processing" @click="decision.post(`${base}/approve`, { preserveScroll: true })">
                        Approve and create record
                    </Button>
                    <Button variant="destructive" :disabled="decision.processing" @click="decision.post(`${base}/reject`, { preserveScroll: true })">
                        Reject
                    </Button>
                </div>
            </CardContent>
        </Card>
        <Card v-else-if="a.decision_note">
            <CardHeader><CardTitle>Decision note</CardTitle></CardHeader>
            <CardContent class="text-sm">{{ a.decision_note }}</CardContent>
        </Card>

        <EnrollmentDetails :a="a" />
    </div>
</template>
