<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import RequestTimeline from '@/components/RequestTimeline.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DocumentRequest } from '@/types/records';

const props = defineProps<{
    request: { data: DocumentRequest };
    actions: string[]; // statuses the request may move to next
    canDownload: boolean;
    canRegenerate: boolean;
}>();

const r = computed(() => props.request.data);
const base = computed(() => `/staff/requests/${r.value.id}`);
const can = (status: string) => props.actions.includes(status);

defineOptions({
    layout: { breadcrumbs: [{ title: 'Document requests', href: '/staff/requests' }] },
});

// One note field: optional when approving, required when rejecting.
const decision = useForm({ note: '' });

function post(action: string): void {
    router.post(`${base.value}/${action}`, {}, { preserveScroll: true });
}

function reject(): void {
    decision.post(`${base.value}/reject`, { preserveScroll: true });
}

function approve(): void {
    decision.post(`${base.value}/approve`, { preserveScroll: true, onSuccess: () => decision.reset() });
}
</script>

<template>
    <Head :title="r.request_no" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h1 class="text-2xl font-semibold">{{ r.request_no }}</h1>
                <p class="text-sm text-muted-foreground">{{ r.document_label }}</p>
            </div>
            <StatusBadge :status="r.status" />
        </div>

        <Card>
            <CardHeader><CardTitle>Request</CardTitle></CardHeader>
            <CardContent class="grid gap-1 text-sm">
                <div>
                    <span class="text-muted-foreground">Learner:</span>
                    <Link v-if="r.student" :href="`/staff/students/${r.student.id}`" class="underline">
                        {{ r.student.full_name }}
                    </Link>
                    (LRN {{ r.student?.lrn ?? 'not assigned' }})
                </div>
                <div><span class="text-muted-foreground">Requested by:</span> {{ r.requester?.name }} ({{ r.requester?.email }})</div>
                <div><span class="text-muted-foreground">Purpose:</span> {{ r.purpose }}</div>
                <div><span class="text-muted-foreground">Copies:</span> {{ r.copies }}</div>
                <div><span class="text-muted-foreground">Sent:</span> {{ r.created_at }}</div>
                <div v-if="r.remarks"><span class="text-muted-foreground">Note:</span> {{ r.remarks }}</div>
            </CardContent>
        </Card>

        <Card v-if="actions.length || canRegenerate || canDownload">
            <CardHeader><CardTitle>Actions</CardTitle></CardHeader>
            <CardContent class="space-y-4">
                <div v-if="can('approved') || can('rejected')" class="grid max-w-xl gap-2">
                    <Label for="note">Note (required to reject)</Label>
                    <Input id="note" v-model="decision.note" maxlength="500" />
                    <InputError :message="decision.errors.note" />
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button v-if="can('under_review')" variant="outline" @click="post('review')" title="Mark the request as being reviewed">Start review</Button>
                    <Button v-if="can('approved')" :disabled="decision.processing" @click="approve" title="Approve it and create the PDF document">
                        Approve and generate PDF
                    </Button>
                    <Button v-if="can('rejected')" variant="destructive" :disabled="decision.processing" @click="reject" title="Reject it. A note for the requester is required">
                        Reject
                    </Button>
                    <Button v-if="can('released')" @click="post('release')" title="Let the requester download the document">Release to requester</Button>
                    <Button v-if="canRegenerate" variant="outline" @click="post('regenerate')" title="Run the PDF creation again">
                        Generate PDF again
                    </Button>
                    <Button v-if="canDownload" variant="outline" as-child>
                        <a :href="`/documents/request/${r.id}`"><Download class="size-4" /> Download PDF</a>
                    </Button>
                </div>

                <p v-if="r.status === 'processing' || r.status === 'approved'" class="text-sm text-muted-foreground">
                    Generating the PDF. Reload to see the new status.
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader><CardTitle>Status history</CardTitle></CardHeader>
            <CardContent><RequestTimeline :history="r.history ?? []" /></CardContent>
        </Card>
    </div>
</template>
