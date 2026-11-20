<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import NativeSelect from '@/components/NativeSelect.vue';
import SimplePagination from '@/components/SimplePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type Row = {
    id: number;
    when: string;
    event: string;
    label: string;
    who: string | null;
    email: string | null;
    ip: string | null;
    browser: string | null;
};

type Page = {
    data: Row[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

const props = defineProps<{
    logs: Page;
    filters: { event: string | null; q: string };
    events: { value: string; label: string }[];
    recent: { logins: number; failed: number; registered: number };
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Session logs', href: '/admin/session-logs' }] },
});

const event = ref<string | number | null>(props.filters.event ?? '');
const q = ref(props.filters.q ?? '');

function search(): void {
    router.get('/admin/session-logs', { event: event.value || undefined, q: q.value || undefined }, { preserveState: true, replace: true });
}

let timer: ReturnType<typeof setTimeout>;
watch(q, () => {
    clearTimeout(timer);
    timer = setTimeout(search, 350);
});
watch(event, search);

// Failed attempts and lockouts are shown in a warning colour.
const variant = (e: string) => (e === 'login_failed' || e === 'locked_out' || e === 'two_factor_failed' ? 'destructive' : e === 'login' ? 'default' : 'secondary');
</script>

<template>
    <Head title="Session logs" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div>
            <h1 class="text-2xl font-semibold">Session logs</h1>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <Card><CardHeader><CardDescription>Logins, last 24 hours</CardDescription><CardTitle class="text-3xl">{{ recent.logins }}</CardTitle></CardHeader></Card>
            <Card><CardHeader><CardDescription>Failed logins and lockouts, last 24 hours</CardDescription><CardTitle class="text-3xl">{{ recent.failed }}</CardTitle></CardHeader></Card>
            <Card><CardHeader><CardDescription>New registrations, last 24 hours</CardDescription><CardTitle class="text-3xl">{{ recent.registered }}</CardTitle></CardHeader></Card>
        </div>

        <div class="grid max-w-2xl gap-2 md:grid-cols-2">
            <Input v-model="q" placeholder="Search an email address or IP" />
            <NativeSelect v-model="event" :options="events" placeholder="All events" />
        </div>

        <div class="overflow-x-auto border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">When</th>
                        <th class="p-3">Event</th>
                        <th class="p-3">Account</th>
                        <th class="p-3">IP</th>
                        <th class="p-3">Browser</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="l in logs.data" :key="l.id" class="border-t align-top">
                        <td class="p-3 whitespace-nowrap">{{ l.when }}</td>
                        <td class="p-3"><Badge :variant="variant(l.event)">{{ l.label }}</Badge></td>
                        <td class="p-3">
                            <div v-if="l.who" class="font-medium">{{ l.who }}</div>
                            <div class="text-muted-foreground">{{ l.email ?? '-' }}</div>
                        </td>
                        <td class="p-3">{{ l.ip ?? '-' }}</td>
                        <td class="max-w-xs truncate p-3 text-muted-foreground" :title="l.browser ?? ''">{{ l.browser ?? '-' }}</td>
                    </tr>
                    <tr v-if="logs.data.length === 0">
                        <td colspan="5" class="p-6 text-center text-muted-foreground">No entries.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <SimplePagination :page="logs" />
    </div>
</template>
