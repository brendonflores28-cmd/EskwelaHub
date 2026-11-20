<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

type Props = {
    portal: 'student' | 'staff' | 'admin';
    stats: Record<string, number | string | null>;
};

defineProps<Props>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] },
});

const labels: Record<string, string> = {
    students: 'Student records',
    enrolled_now: 'Enrolled this school year',
    graduates: 'Graduates on file',
    pending_links: 'Pending link requests',
    linked_records: 'Linked records',
};

const titles = {
    student: 'My portal',
    staff: 'Registrar dashboard',
    admin: 'Principal dashboard',
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold">{{ titles[portal] }}</h1>
            <p v-if="stats.school_year" class="text-sm text-muted-foreground">
                School year {{ stats.school_year }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <template v-for="(value, key) in stats" :key="key">
                <Card v-if="labels[key]">
                    <CardHeader>
                        <CardDescription>{{ labels[key] }}</CardDescription>
                        <CardTitle class="text-3xl">{{ value }}</CardTitle>
                    </CardHeader>
                </Card>
            </template>
        </div>

        <Card v-if="portal === 'student'">
            <CardHeader>
                <CardTitle>Your school records</CardTitle>
                <CardDescription>
                    Link your account to a student record to see it here. The registrar confirms each link.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Button as-child><Link href="/my-records">Open my records</Link></Button>
            </CardContent>
        </Card>

        <Card v-else>
            <CardHeader>
                <CardTitle>Quick links</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-wrap gap-2">
                <Button as-child><Link href="/staff/students">Search student records</Link></Button>
                <Button variant="outline" as-child><Link href="/staff/links">Review link requests</Link></Button>
            </CardContent>
        </Card>
    </div>
</template>
