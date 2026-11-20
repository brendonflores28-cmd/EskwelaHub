<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

type LinkRequest = {
    id: number;
    relationship: string;
    requested_at: string;
    user: { name: string; email: string };
    student: { id: number; lrn: string | null; full_name: string; birthdate: string };
};

defineProps<{ links: LinkRequest[] }>();

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
            <p class="text-sm text-muted-foreground">
                People who registered and asked to see a student record (not document requests). The LRN and birthdate already matched; confirm the person is
                really the learner or their parent or guardian before approving.
            </p>
        </div>

        <div class="overflow-x-auto rounded-xl border">
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
                    <tr v-for="l in links" :key="l.id" class="border-t">
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
                            <Button size="sm" @click="review(l.id, 'approve')">Approve</Button>
                            <Button size="sm" variant="outline" @click="review(l.id, 'reject')">Reject</Button>
                        </td>
                    </tr>
                    <tr v-if="links.length === 0">
                        <td colspan="5" class="p-6 text-center text-muted-foreground">No pending requests.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
