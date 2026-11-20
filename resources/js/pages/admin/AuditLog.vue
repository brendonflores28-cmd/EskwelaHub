<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import NativeSelect from '@/components/NativeSelect.vue';
import SimplePagination from '@/components/SimplePagination.vue';

type Entry = {
    id: number;
    when: string;
    log: string;
    event: string | null;
    description: string;
    who: string;
    subject: string | null;
    ip: string | null;
};

type Page = {
    data: Entry[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    entries: Page;
    filters: { log: string | null; event: string | null };
    logs: string[];
    events: string[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Audit trail', href: '/admin/audit-log' }] },
});

const log = ref<string | number | null>(props.filters.log ?? '');
const event = ref<string | number | null>(props.filters.event ?? '');

watch([log, event], () => {
    router.get(
        '/admin/audit-log',
        { log: log.value || undefined, event: event.value || undefined },
        { preserveState: true, replace: true },
    );
});

const toOptions = (items: string[]) => items.map((i) => ({ value: i, label: i }));
</script>

<template>
    <Head title="Audit trail" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div>
            <h1 class="text-2xl font-semibold">Audit trail</h1>
            <p class="text-sm text-muted-foreground">
                What people did inside the system: who viewed, changed, requested, approved or released which record.
                Sign-ins, logouts and registrations are in the
                <Link href="/admin/session-logs" class="underline">Session logs</Link>.
            </p>
        </div>

        <div class="grid max-w-lg gap-2 md:grid-cols-2">
            <NativeSelect v-model="log" :options="toOptions(logs)" placeholder="All areas" />
            <NativeSelect v-model="event" :options="toOptions(events)" placeholder="All actions" />
        </div>

        <div class="overflow-x-auto border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">When</th>
                        <th class="p-3">Who</th>
                        <th class="p-3">Action</th>
                        <th class="p-3">Record</th>
                        <th class="p-3">IP</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="e in entries.data" :key="e.id" class="border-t">
                        <td class="p-3 whitespace-nowrap">{{ e.when }}</td>
                        <td class="p-3">{{ e.who }}</td>
                        <td class="p-3">
                            {{ e.description }}
                            <span class="text-muted-foreground">({{ e.log }}/{{ e.event }})</span>
                        </td>
                        <td class="p-3">{{ e.subject ?? '-' }}</td>
                        <td class="p-3">{{ e.ip ?? '-' }}</td>
                    </tr>
                    <tr v-if="entries.data.length === 0">
                        <td colspan="5" class="p-6 text-center text-muted-foreground">No entries.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <SimplePagination :page="entries" />
    </div>
</template>
