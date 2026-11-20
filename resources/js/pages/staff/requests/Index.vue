<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import NativeSelect from '@/components/NativeSelect.vue';
import SimplePagination from '@/components/SimplePagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Input } from '@/components/ui/input';
import type { DocumentRequest, Option, Paginated } from '@/types/records';

const props = defineProps<{
    requests: Paginated<DocumentRequest>;
    filters: { status: string | null; q: string };
    statuses: Option[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Document requests', href: '/staff/requests' }] },
});

const status = ref<string | number | null>(props.filters.status ?? '');
const q = ref(props.filters.q ?? '');

function search(): void {
    router.get(
        '/staff/requests',
        { status: status.value || undefined, q: q.value || undefined },
        { preserveState: true, replace: true },
    );
}

let timer: ReturnType<typeof setTimeout>;
watch(q, () => {
    clearTimeout(timer);
    timer = setTimeout(search, 350);
});
watch(status, search);
</script>

<template>
    <Head title="Document requests" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-semibold">Document requests</h1>

        <div class="grid max-w-xl gap-2 md:grid-cols-2">
            <Input v-model="q" placeholder="Request number" />
            <NativeSelect v-model="status" :options="statuses" placeholder="All statuses" />
        </div>

        <div class="overflow-x-auto border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Request no.</th>
                        <th class="p-3">Document</th>
                        <th class="p-3">Learner</th>
                        <th class="p-3">Requested by</th>
                        <th class="p-3">Sent</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in requests.data" :key="r.id" class="border-t hover:bg-muted/30">
                        <td class="p-3 font-medium">
                            <Link :href="`/staff/requests/${r.id}`" class="hover:underline">{{ r.request_no }}</Link>
                        </td>
                        <td class="p-3">{{ r.document_label }}</td>
                        <td class="p-3">{{ r.student?.full_name }}</td>
                        <td class="p-3">{{ r.requester?.name }}</td>
                        <td class="p-3">{{ r.created_at }}</td>
                        <td class="p-3"><StatusBadge :status="r.status" /></td>
                    </tr>
                    <tr v-if="requests.data.length === 0">
                        <td colspan="6" class="p-6 text-center text-muted-foreground">No requests.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <SimplePagination :page="requests" />
    </div>
</template>
