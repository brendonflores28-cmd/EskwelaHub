<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Printer } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Learner = { enrollment_id: number; student_id: number; full_name: string; lrn: string | null; sex: string; status: string };

const props = defineProps<{
    section: {
        id: number;
        grade_level: number;
        name: string;
        adviser_name: string | null;
        capacity: number | null;
        learners: number;
        school_year: string;
        school_year_id: number;
    };
    members: Learner[];
    waiting: Learner[];
    canManage: boolean;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Class sections', href: '/staff/sections' }] },
});

const seatsLeft = computed(() => (props.section.capacity === null ? null : Math.max(0, props.section.capacity - props.section.learners)));
const boys = computed(() => props.members.filter((m) => m.sex === 'M').length);
const girls = computed(() => props.members.filter((m) => m.sex === 'F').length);

// Tick learners in one of the two lists and move them with one click.
const toAdd = ref<number[]>([]);
const toRemove = ref<number[]>([]);

function toggle(list: number[], id: number): void {
    const i = list.indexOf(id);
    if (i >= 0) list.splice(i, 1);
    else list.push(id);
}

const errors = ref<Record<string, string>>({});

function move(action: 'assign' | 'unassign', ids: number[]): void {
    router.post(`/staff/sections/${props.section.id}/${action}`, { enrollment_ids: ids }, {
        preserveScroll: true,
        onError: (e) => (errors.value = e),
        onSuccess: () => {
            errors.value = {};
            toAdd.value = [];
            toRemove.value = [];
        },
    });
}

// Edit name, adviser and capacity.
const edit = useForm({ name: props.section.name, adviser_name: props.section.adviser_name ?? '', capacity: props.section.capacity ?? ('' as string | number) });

/** Opens the browser's print dialog; buttons and edit forms are hidden in print (print:hidden). */
function printList(): void {
    window.print();
}

function remove(): void {
    if (confirm('Delete this section? This is only possible when it has no learners.')) {
        router.delete(`/staff/sections/${props.section.id}`);
    }
}
</script>

<template>
    <Head :title="`Grade ${section.grade_level} - ${section.name}`" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h1 class="text-2xl font-semibold">Grade {{ section.grade_level }} - {{ section.name }}</h1>
                <p class="text-sm text-muted-foreground">
                    School year {{ section.school_year }} - adviser: {{ section.adviser_name ?? 'not set' }} -
                    {{ section.learners }}<span v-if="section.capacity"> of {{ section.capacity }}</span> learners
                    ({{ boys }} boys, {{ girls }} girls)
                </p>
            </div>
            <div class="flex gap-2 print:hidden">
                <Button variant="outline" size="sm" @click="printList"><Printer class="size-4" /> Print class list</Button>
                <Button variant="outline" size="sm" as-child>
                    <Link :href="`/staff/sections?school_year_id=${section.school_year_id}`">All sections</Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Class list</CardTitle>
                    <CardDescription>Learners in this section.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <table class="w-full text-sm">
                        <thead class="text-left text-muted-foreground">
                            <tr>
                                <th v-if="canManage" class="w-8 print:hidden"></th>
                                <th class="py-1">#</th>
                                <th>Name</th>
                                <th>LRN</th>
                                <th>Sex</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(m, i) in members" :key="m.enrollment_id" class="border-t">
                                <td v-if="canManage" class="print:hidden">
                                    <input type="checkbox" :checked="toRemove.includes(m.enrollment_id)" @change="toggle(toRemove, m.enrollment_id)" />
                                </td>
                                <td class="py-1">{{ i + 1 }}</td>
                                <td>
                                    <Link :href="`/staff/students/${m.student_id}`" class="hover:underline">{{ m.full_name }}</Link>
                                </td>
                                <td>{{ m.lrn ?? '-' }}</td>
                                <td>{{ m.sex }}</td>
                            </tr>
                            <tr v-if="members.length === 0">
                                <td colspan="5" class="py-4 text-center text-muted-foreground">No learners in this section yet.</td>
                            </tr>
                        </tbody>
                    </table>
                    <Button v-if="canManage && toRemove.length" size="sm" variant="outline" class="print:hidden" @click="move('unassign', toRemove)">
                        Remove {{ toRemove.length }} from this section
                    </Button>
                </CardContent>
            </Card>

            <Card v-if="canManage" class="print:hidden">
                <CardHeader>
                    <CardTitle>Without a section</CardTitle>
                    <CardDescription>
                        Grade {{ section.grade_level }} learners of {{ section.school_year }} who have no section yet.
                        <span v-if="seatsLeft !== null">{{ seatsLeft }} place(s) left here.</span>
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <ul class="max-h-96 divide-y overflow-y-auto text-sm">
                        <li v-for="w in waiting" :key="w.enrollment_id" class="flex items-center gap-2 py-1">
                            <input type="checkbox" :checked="toAdd.includes(w.enrollment_id)" @change="toggle(toAdd, w.enrollment_id)" />
                            <span>{{ w.full_name }}</span>
                            <span class="text-muted-foreground">{{ w.lrn ?? '' }}</span>
                        </li>
                        <li v-if="waiting.length === 0" class="py-3 text-muted-foreground">Everyone in this grade has a section.</li>
                    </ul>
                    <InputError :message="errors.enrollment_ids" />
                    <Button v-if="toAdd.length" size="sm" @click="move('assign', toAdd)">Add {{ toAdd.length }} to this section</Button>
                </CardContent>
            </Card>
        </div>

        <Card v-if="canManage" class="print:hidden">
            <CardHeader><CardTitle>Section details</CardTitle></CardHeader>
            <CardContent>
                <form class="grid gap-3 md:grid-cols-4" @submit.prevent="edit.put(`/staff/sections/${section.id}`, { preserveScroll: true })">
                    <div class="grid gap-1">
                        <Label>Name</Label>
                        <Input v-model="edit.name" required />
                        <InputError :message="edit.errors.name" />
                    </div>
                    <div class="grid gap-1">
                        <Label>Adviser</Label>
                        <Input v-model="edit.adviser_name" />
                    </div>
                    <div class="grid gap-1">
                        <Label>Capacity</Label>
                        <Input v-model="edit.capacity" inputmode="numeric" />
                        <InputError :message="edit.errors.capacity" />
                    </div>
                    <div class="flex items-end gap-2">
                        <Button type="submit" size="sm" :disabled="edit.processing">Save</Button>
                        <Button type="button" size="sm" variant="destructive" @click="remove">Delete section</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
