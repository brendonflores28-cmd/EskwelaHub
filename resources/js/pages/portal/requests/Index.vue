<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import SimplePagination from '@/components/SimplePagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import type { DocumentRequest, Paginated } from '@/types/records';

defineProps<{ requests: Paginated<DocumentRequest> }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'My requests', href: '/my-requests' }] },
});
</script>

<template>
    <Head title="My requests" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold">My document requests</h1>
            <Button as-child>
                <Link href="/my-requests/create"><Plus class="size-4" /> New request</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Request no.</th>
                        <th class="p-3">Document</th>
                        <th class="p-3">Learner</th>
                        <th class="p-3">Sent</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in requests.data" :key="r.id" class="border-t hover:bg-muted/30">
                        <td class="p-3 font-medium">
                            <Link :href="`/my-requests/${r.id}`" class="hover:underline">{{ r.request_no }}</Link>
                        </td>
                        <td class="p-3">{{ r.document_label }}</td>
                        <td class="p-3">{{ r.student?.full_name }}</td>
                        <td class="p-3">{{ r.created_at }}</td>
                        <td class="p-3"><StatusBadge :status="r.status" /></td>
                    </tr>
                    <tr v-if="requests.data.length === 0">
                        <td colspan="5" class="p-6 text-center text-muted-foreground">You have no requests yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <SimplePagination :page="requests" />
    </div>
</template>
