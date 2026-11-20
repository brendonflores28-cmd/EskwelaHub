<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SchoolYearOption, StrandOption } from '@/types/records';

type SectionRow = { id: number; grade_level: number; strand: string | null; strand_short: string | null; name: string; adviser_name: string | null; capacity: number | null; learners: number };
type Grade = { grade: number; enrolled: number; unassigned: number; sections: SectionRow[] };

const props = defineProps<{
    schoolYearId: number;
    schoolYears: SchoolYearOption[];
    grades: Grade[];
    canManage: boolean;
    strands: StrandOption[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Class sections', href: '/staff/sections' }] },
});

const year = ref<string | number>(props.schoolYearId);
const yearOptions = props.schoolYears.map((y) => ({ value: y.id, label: y.label }));

watch(year, () => {
    router.get('/staff/sections', { school_year_id: year.value }, { preserveState: false });
});

// One small form to add a section; the grade is picked on the card where "Add section" was pressed.
const adding = ref<number | null>(null);
const form = useForm({ school_year_id: props.schoolYearId, grade_level: 7, strand: '' as string, name: '', adviser_name: '', capacity: '' as string | number });

function openAdd(grade: number): void {
    adding.value = grade;
    form.grade_level = grade;
    form.reset('name', 'strand', 'adviser_name', 'capacity');
    form.clearErrors();
}

function save(): void {
    form.post('/staff/sections', { preserveScroll: true, onSuccess: () => (adding.value = null) });
}
</script>

<template>
    <Head title="Class sections" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="text-2xl font-semibold">Class sections</h1>
            </div>
            <div class="w-48">
                <Label class="mb-1 block text-xs">School year</Label>
                <NativeSelect v-model="year" :options="yearOptions" />
            </div>
        </div>

        <Card v-for="g in grades" :key="g.grade">
            <CardHeader class="flex-row items-start justify-between gap-2">
                <div>
                    <CardTitle>Grade {{ g.grade }}</CardTitle>
                    <CardDescription>
                        {{ g.enrolled }} enrolled
                        <Badge v-if="g.unassigned > 0" variant="secondary" class="ml-2">{{ g.unassigned }} without a section</Badge>
                    </CardDescription>
                </div>
                <Button v-if="canManage" size="sm" variant="outline" @click="openAdd(g.grade)"><Plus class="size-4" /> Add section</Button>
            </CardHeader>
            <CardContent class="space-y-3">
                <form v-if="adding === g.grade" class="grid gap-3 border p-3 md:grid-cols-4" @submit.prevent="save">
                    <div v-if="g.grade >= 11" class="grid gap-1 md:col-span-4">
                        <Label>Strand</Label>
                        <NativeSelect v-model="form.strand" :options="strands.map((s) => ({ value: s.value, label: s.label }))" placeholder="Choose a strand" />
                        <InputError :message="form.errors.strand" />
                    </div>
                    <div class="grid gap-1">
                        <Label>Section name</Label>
                        <Input v-model="form.name" placeholder="for example Rizal" required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="grid gap-1">
                        <Label>Adviser (optional)</Label>
                        <Input v-model="form.adviser_name" />
                    </div>
                    <div class="grid gap-1">
                        <Label>Capacity (optional)</Label>
                        <Input v-model="form.capacity" inputmode="numeric" />
                        <InputError :message="form.errors.capacity" />
                    </div>
                    <div class="flex items-end gap-2">
                        <Button type="submit" size="sm" :disabled="form.processing">Save</Button>
                        <Button type="button" size="sm" variant="ghost" @click="adding = null">Cancel</Button>
                    </div>
                </form>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Link v-for="s in g.sections" :key="s.id" :href="`/staff/sections/${s.id}`" class="border p-3 transition hover:bg-muted/40">
                        <div class="flex items-center gap-2 font-medium">
                            {{ s.name }}
                            <Badge v-if="s.strand_short" variant="secondary">{{ s.strand_short }}</Badge>
                        </div>
                        <div class="text-sm text-muted-foreground">{{ s.adviser_name ?? 'No adviser yet' }}</div>
                        <div class="mt-1 text-sm">
                            {{ s.learners }}<span v-if="s.capacity"> / {{ s.capacity }}</span> learners
                            <Badge v-if="s.capacity && s.learners >= s.capacity" variant="destructive" class="ml-1">Full</Badge>
                        </div>
                    </Link>
                </div>
                <p v-if="g.sections.length === 0" class="text-sm text-muted-foreground">No sections yet.</p>
            </CardContent>
        </Card>
    </div>
</template>
