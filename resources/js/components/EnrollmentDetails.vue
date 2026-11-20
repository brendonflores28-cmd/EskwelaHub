<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { EnrollmentForm } from '@/types/records';

/** Read-only summary of an enrollment form and its documents. Shared by the portal and staff pages. */
defineProps<{ a: EnrollmentForm }>();

const kindLabels: Record<string, string> = {
    psa_birth_certificate: 'PSA birth certificate',
    report_card: 'Report card',
    good_moral: 'Good moral certificate',
    other_document: 'Other document',
};
</script>

<template>
    <Card>
        <CardHeader><CardTitle>Learner</CardTitle></CardHeader>
        <CardContent class="grid gap-1 text-sm md:grid-cols-2">
            <div><span class="text-muted-foreground">Name:</span> {{ a.full_name }}</div>
            <div><span class="text-muted-foreground">LRN:</span> {{ a.lrn ?? 'none' }}</div>
            <div><span class="text-muted-foreground">Birthdate:</span> {{ a.birthdate }}</div>
            <div><span class="text-muted-foreground">Sex:</span> {{ a.sex === 'M' ? 'Male' : 'Female' }}</div>
            <div><span class="text-muted-foreground">Type:</span> {{ a.type_label }}, Grade {{ a.grade_level }}</div>
            <div v-if="a.previous_school"><span class="text-muted-foreground">Previous school:</span> {{ a.previous_school }}</div>
            <div class="md:col-span-2"><span class="text-muted-foreground">Address:</span> {{ a.address }}</div>
            <div><span class="text-muted-foreground">Guardian:</span> {{ a.guardian_name }} ({{ a.relationship }})</div>
            <div><span class="text-muted-foreground">Contact:</span> {{ a.guardian_contact }}</div>
        </CardContent>
    </Card>

    <Card>
        <CardHeader><CardTitle>Documents</CardTitle></CardHeader>
        <CardContent>
            <ul class="divide-y text-sm">
                <li v-for="d in a.documents" :key="d.id" class="flex items-center justify-between py-2">
                    <span>{{ kindLabels[d.kind] ?? d.kind }} <span class="text-muted-foreground">({{ d.original_name }}, {{ d.size_kb }} KB)</span></span>
                    <a :href="`/enrollment-documents/request/${d.id}`" target="_blank" class="underline">Open</a>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
