<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, FileText, GraduationCap, ScanLine, TriangleAlert, UserCheck, UserPlus } from '@lucide/vue';
import { computed } from 'vue';
import Tip from '@/components/Tip.vue';
import { dashboard } from '@/routes';

/**
 * Two dashboards in one file, chosen by the role:
 *  - student (learner, parent, alumni): three big actions, then the state of their requests;
 *  - staff (registrar): a to-do list of what needs action now, then three overview numbers.
 */
type Props = {
    portal: 'student' | 'staff';
    stats: Record<string, number | string | null>;
};

const props = defineProps<Props>();
const page = usePage();
const n = (key: string) => Number(props.stats[key] ?? 0);

defineOptions({
    layout: { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] },
});

const firstName = computed(() => String(page.props.auth.user.name).split(' ')[0]);

const actions = computed(() => [
    {
        icon: ClipboardList,
        title: 'My records',
        text: n('linked_records') ? 'See the learner\'s school record' : 'Link your child\'s record first',
        href: '/my-records',
        tip: 'Open the school records linked to your account, or link a new one with the LRN and birthdate.',
        tour: 'act-records',
    },
    {
        icon: FileText,
        title: 'Request a document',
        text: 'Form 137, certificates',
        href: '/my-requests/create',
        tip: 'Ask the registrar for Form 137, a certificate of graduation or of enrollment.',
        tour: 'act-request',
    },
    {
        icon: UserPlus,
        title: 'Enroll a learner',
        text: 'Grade 7 and transferees',
        href: '/my-enrollments/create',
        tip: 'Fill in the online enrollment form and upload the documents.',
        tour: 'act-enroll',
    },
]);

const status = computed(() => [
    { label: 'Open requests', value: n('open_requests'), href: '/my-requests', tip: 'Document requests the registrar is still working on.' },
    { label: 'Documents ready', value: n('ready_to_download'), href: '/my-requests', tip: 'Released documents you can download now.' },
    { label: 'Enrollment forms in review', value: n('open_enrollments'), href: '/my-enrollments', tip: 'Enrollment forms waiting for the registrar\'s decision.' },
    { label: 'Records waiting approval', value: n('pending_links'), href: '/my-records', tip: 'Record links the registrar has not approved yet.' },
]);

const todo = computed(() => [
    { icon: FileText, label: 'Document requests', text: 'to review or release', value: n('todo_requests'), href: '/staff/requests', tip: 'Requests that are new, under review, or ready to release.', tour: 'todo-requests' },
    { icon: UserPlus, label: 'Enrollment forms', text: 'waiting for a decision', value: n('todo_enrollments'), href: '/staff/enrollments', tip: 'Online enrollment forms to approve or reject.', tour: 'todo-enrollments' },
    { icon: UserCheck, label: 'Account links', text: 'to approve', value: n('todo_links'), href: '/staff/links', tip: 'Parents and learners asking to see a student record.', tour: 'todo-links' },
    { icon: ScanLine, label: 'Scanned records', text: 'to check', value: n('todo_scans'), href: '/staff/ocr', tip: 'Scans whose text was read and waits for you to verify.', tour: 'todo-scans' },
    { icon: TriangleAlert, label: 'Possible duplicates', text: 'to review', value: n('todo_alerts'), href: '/staff/alerts', tip: 'Two records that may be the same learner.', tour: 'todo-alerts' },
]);

const overview = computed(() => [
    { label: 'Student records', value: n('students'), href: '/staff/students', tip: 'All learner records, past and present.' },
    { label: 'Enrolled this school year', value: n('enrolled_now'), href: '/staff/sections', tip: 'Learners enrolled in the current school year.' },
    { label: 'Graduates on file', value: n('graduates'), href: '/staff/students?status=graduated', tip: 'Learners marked as graduated.' },
]);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <!-- Learner, parent, alumni -->
        <template v-if="portal === 'student'">
            <h1 class="text-2xl font-semibold">Hello, {{ firstName }}</h1>

            <section data-tour="dash-actions" class="grid gap-3 md:grid-cols-3">
                <Tip v-for="a in actions" :key="a.title" :text="a.tip">
                    <Link :href="a.href" :data-tour="a.tour" class="group flex items-start gap-4 border border-t-4 border-t-primary bg-card p-5 transition hover:bg-muted/40">
                        <component :is="a.icon" class="mt-0.5 size-7 shrink-0 text-primary" />
                        <span>
                            <span class="block font-serif text-lg font-bold">{{ a.title }}</span>
                            <span class="block text-sm text-muted-foreground">{{ a.text }}</span>
                        </span>
                    </Link>
                </Tip>
            </section>

            <section data-tour="dash-status">
                <h2 class="mb-2 text-lg font-semibold">Where things stand</h2>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Tip v-for="s in status" :key="s.label" :text="s.tip">
                        <Link :href="s.href" class="block border bg-card p-4 transition hover:border-primary">
                            <div class="text-sm text-muted-foreground">{{ s.label }}</div>
                            <div class="mt-1 font-serif text-3xl font-bold">{{ s.value }}</div>
                        </Link>
                    </Tip>
                </div>
            </section>
        </template>

        <!-- Registrar -->
        <template v-else>
            <div>
                <h1 class="text-2xl font-semibold">Registrar dashboard</h1>
                <p class="text-sm text-muted-foreground">School year {{ stats.school_year ?? '-' }}</p>
            </div>

            <section data-tour="dash-todo">
                <h2 class="mb-2 text-lg font-semibold">Needs your action</h2>
                <ul class="divide-y border bg-card">
                    <li v-for="t in todo" :key="t.label">
                        <Tip :text="t.tip" side="top">
                            <Link :href="t.href" :data-tour="t.tour" class="flex items-center gap-4 px-4 py-3 transition hover:bg-muted/40">
                                <component :is="t.icon" class="size-5 shrink-0 text-primary" />
                                <span class="flex-1">
                                    <span class="font-medium">{{ t.label }}</span>
                                    <span class="text-sm text-muted-foreground"> {{ t.text }}</span>
                                </span>
                                <span :class="['min-w-10 px-2 py-0.5 text-center text-sm font-bold', t.value > 0 ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground']">{{ t.value }}</span>
                            </Link>
                        </Tip>
                    </li>
                </ul>
            </section>

            <section data-tour="dash-overview">
                <h2 class="mb-2 text-lg font-semibold">Overview</h2>
                <div class="grid gap-3 sm:grid-cols-3">
                    <Tip v-for="o in overview" :key="o.label" :text="o.tip">
                        <Link :href="o.href" class="flex items-center gap-3 border bg-card p-4 transition hover:border-primary">
                            <GraduationCap class="size-6 shrink-0 text-primary" />
                            <span>
                                <span class="block text-sm text-muted-foreground">{{ o.label }}</span>
                                <span class="block font-serif text-2xl font-bold">{{ o.value }}</span>
                            </span>
                        </Link>
                    </Tip>
                </div>
            </section>
        </template>
    </div>
</template>
