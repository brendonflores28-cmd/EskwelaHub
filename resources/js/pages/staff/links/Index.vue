<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import SimplePagination from '@/components/SimplePagination.vue';
import { Button } from '@/components/ui/button';

type LinkRequest = {
    id: number;
    relationship: string;
    requested_at: string;
    user: { name: string; email: string };
    student: { id: number; lrn: string | null; full_name: string; birthdate: string };
};

defineProps<{ links: { data: LinkRequest[]; prev_page_url: string | null; next_page_url: string | null; current_page: number; last_page: number; total: number; from: number | null; to: number | null } }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Link requests', href: '/staff/links' }] },
});

function review(id: number, action: 'approve' | 'reject'): void {
    router.post(`/staff/links/${id}/${action}`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Link requests" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div>
            <h1 class="text-2xl font-semibold">Account link approvals</h1>
        </div>

        <div class="overflow-x-auto border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left">
                    <tr>
                        <th class="p-3">Account</th>
                        <th class="p-3">Relationship</th>
                        <th class="p-3">Student</th>
                        <th class="p-3">Requested</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="l in links.data" :key="l.id" class="border-t">
                        <td class="p-3">
                            <div class="font-medium">{{ l.user.name }}</div>
                            <div class="text-muted-foreground">{{ l.user.email }}</div>
                        </td>
                        <td class="p-3 capitalize">{{ l.relationship }}</td>
                        <td class="p-3">
                            <Link :href="`/staff/students/${l.student.id}`" class="hover:underline">
                                {{ l.student.full_name }}
                            </Link>
                            <div class="text-muted-foreground">LRN {{ l.student.lrn }}</div>
                        </td>
                        <td class="p-3">{{ l.requested_at }}</td>
                        <td class="flex gap-2 p-3">
                            <Button size="sm" @click="review(l.id, 'approve')" title="Let this account see the student record">Approve</Button>
                            <Button size="sm" variant="outline" @click="review(l.id, 'reject')" title="Do not give this account access">Reject</Button>
                        </td>
                    </tr>
                    <tr v-if="links.data.length === 0">
                        <td colspan="5" class="p-6 text-center text-muted-foreground">No pending requests.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <SimplePagination :page="links" />
    </div>
</template>
