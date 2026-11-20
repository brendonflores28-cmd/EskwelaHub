<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { computed } from 'vue';
import RequestTimeline from '@/components/RequestTimeline.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { DocumentRequest } from '@/types/records';

const props = defineProps<{ request: { data: DocumentRequest }; canDownload: boolean }>();
const r = computed(() => props.request.data);

defineOptions({
    layout: { breadcrumbs: [{ title: 'My requests', href: '/my-requests' }] },
});
</script>

<template>
    <Head :title="r.request_no" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h1 class="text-2xl font-semibold">{{ r.request_no }}</h1>
                <p class="text-sm text-muted-foreground">{{ r.document_label }} for {{ r.student?.full_name }}</p>
            </div>
            <StatusBadge :status="r.status" />
        </div>

        <Card v-if="r.remarks">
            <CardHeader><CardTitle>Note from the registrar</CardTitle></CardHeader>
            <CardContent class="text-sm">{{ r.remarks }}</CardContent>
        </Card>

        <Card v-if="canDownload">
            <CardHeader><CardTitle>Your document</CardTitle></CardHeader>
            <CardContent class="space-y-2 text-sm">
                <p>The registrar released your document. The download link works for two minutes after you click.</p>
                <Button as-child>
                    <a :href="`/documents/request/${r.id}`"><Download class="size-4" /> Download PDF</a>
                </Button>
            </CardContent>
        </Card>
        <p v-else-if="r.status === 'ready'" class="text-sm text-muted-foreground">
            Your document is ready. You can download it as soon as the registrar releases it.
        </p>

        <Card>
            <CardHeader><CardTitle>Details</CardTitle></CardHeader>
            <CardContent class="grid gap-1 text-sm">
                <div><span class="text-muted-foreground">Purpose:</span> {{ r.purpose }}</div>
                <div><span class="text-muted-foreground">Copies:</span> {{ r.copies }}</div>
                <div><span class="text-muted-foreground">Sent:</span> {{ r.created_at }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader><CardTitle>Progress</CardTitle></CardHeader>
            <CardContent><RequestTimeline :history="r.history ?? []" /></CardContent>
        </Card>
    </div>
</template>
