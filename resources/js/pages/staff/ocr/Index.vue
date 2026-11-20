<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Upload } from '@lucide/vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';

type Draft = { id: number; file: string; status: string; uploaded_by: string | null; at: string; student_id: number | null };

defineProps<{ drafts: { data: Draft[] } }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Digitize records', href: '/staff/ocr' }] },
});
</script>

<template>
    <Head title="Digitize records" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Digitize old records</h1>
                <p class="text-sm text-muted-foreground">Upload a scan, check the text, create the record.</p>
            </div>
            <Button as-child>
                <Link href="/staff/ocr/create"><Upload class="size-4" /> Upload a scan</Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">File</th>
                        <th class="p-3">Uploaded by</th>
                        <th class="p-3">When</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="d in drafts.data" :key="d.id" class="border-t hover:bg-muted/30">
                        <td class="p-3 font-medium">
                            <Link :href="`/staff/ocr/${d.id}`" class="hover:underline">{{ d.file }}</Link>
                        </td>
                        <td class="p-3">{{ d.uploaded_by }}</td>
                        <td class="p-3">{{ d.at }}</td>
                        <td class="p-3"><StatusBadge :status="d.status" /></td>
                    </tr>
                    <tr v-if="drafts.data.length === 0">
                        <td colspan="4" class="p-6 text-center text-muted-foreground">No scans yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
