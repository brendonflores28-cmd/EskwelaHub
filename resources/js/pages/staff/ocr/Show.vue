<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SchoolYearOption } from '@/types/records';

type Parsed = {
    lrn?: string | null;
    last_name?: string | null;
    first_name?: string | null;
    middle_name?: string | null;
    sex?: string | null;
    birthdate?: string | null;
    school_year?: string | null;
    grade_level?: number | null;
    general_average?: string | null;
};

const props = defineProps<{
    draft: {
        id: number;
        status: string;
        raw_text: string | null;
        parsed: Parsed;
        student_id: number | null;
        file: string;
        is_image: boolean;
        scan_url: string;
    };
    schoolYears: SchoolYearOption[];
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Digitize records', href: '/staff/ocr' }] },
});

const p = props.draft.parsed;
// Pre-select the school year the OCR found, if it exists.
const guessedYear = props.schoolYears.find((y) => y.label === p.school_year)?.id ?? '';

const form = useForm({
    lrn: p.lrn ?? '',
    last_name: p.last_name ?? '',
    first_name: p.first_name ?? '',
    middle_name: p.middle_name ?? '',
    sex: p.sex ?? 'M',
    birthdate: p.birthdate ?? '',
    school_year_id: guessedYear as string | number,
    grade_level: p.grade_level ?? '',
    general_average: p.general_average ?? '',
});

const yearOptions = props.schoolYears.map((y) => ({ value: y.id, label: y.label }));
const grades = [7, 8, 9, 10, 11, 12].map((g) => ({ value: g, label: `Grade ${g}` }));
const pending = props.draft.status === 'pending';
</script>

<template>
    <Head title="Check scanned record" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div>
            <h1 class="text-2xl font-semibold">Check scanned record</h1>
            <p class="text-sm text-muted-foreground">
                {{ draft.file }}. The fields below are guesses from the scan. Compare them with the image and fix any mistake before saving.
            </p>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader><CardTitle>Scan</CardTitle></CardHeader>
                <CardContent class="space-y-3">
                    <img v-if="draft.is_image" :src="draft.scan_url" alt="Scanned record" class="max-h-[600px] w-full rounded border object-contain" />
                    <a v-else :href="draft.scan_url" target="_blank" class="underline">Open the PDF</a>
                    <details>
                        <summary class="cursor-pointer text-sm text-muted-foreground">Text found by OCR</summary>
                        <pre class="mt-2 max-h-60 overflow-auto rounded bg-muted p-2 text-xs whitespace-pre-wrap">{{ draft.raw_text || '(no text found: type the record by hand)' }}</pre>
                    </details>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Record</CardTitle></CardHeader>
                <CardContent>
                    <p v-if="!pending" class="text-sm">
                        This draft was {{ draft.status }}.
                        <Link v-if="draft.student_id" :href="`/staff/students/${draft.student_id}`" class="underline">Open the record</Link>
                    </p>

                    <form v-else class="grid gap-3 md:grid-cols-2" @submit.prevent="form.post(`/staff/ocr/${draft.id}/verify`)">
                        <div class="grid gap-1 md:col-span-2">
                            <Label>LRN (leave empty for old records)</Label>
                            <Input v-model="form.lrn" maxlength="12" inputmode="numeric" />
                            <InputError :message="form.errors.lrn" />
                        </div>
                        <div class="grid gap-1">
                            <Label>Last name</Label>
                            <Input v-model="form.last_name" required />
                            <InputError :message="form.errors.last_name" />
                        </div>
                        <div class="grid gap-1">
                            <Label>First name</Label>
                            <Input v-model="form.first_name" required />
                            <InputError :message="form.errors.first_name" />
                        </div>
                        <div class="grid gap-1">
                            <Label>Middle name</Label>
                            <Input v-model="form.middle_name" />
                        </div>
                        <div class="grid gap-1">
                            <Label>Sex</Label>
                            <NativeSelect v-model="form.sex" :options="[{ value: 'M', label: 'Male' }, { value: 'F', label: 'Female' }]" />
                        </div>
                        <div class="grid gap-1">
                            <Label>Birthdate</Label>
                            <Input v-model="form.birthdate" type="date" required />
                            <InputError :message="form.errors.birthdate" />
                        </div>
                        <div class="grid gap-1">
                            <Label>School year</Label>
                            <NativeSelect v-model="form.school_year_id" :options="yearOptions" placeholder="None" />
                        </div>
                        <div class="grid gap-1">
                            <Label>Grade</Label>
                            <NativeSelect v-model="form.grade_level" :options="grades" placeholder="None" />
                            <InputError :message="form.errors.grade_level" />
                        </div>
                        <div class="grid gap-1">
                            <Label>General average</Label>
                            <Input v-model="form.general_average" inputmode="decimal" />
                            <InputError :message="form.errors.general_average" />
                        </div>
                        <div class="flex flex-wrap gap-2 md:col-span-2">
                            <Button type="submit" :disabled="form.processing">I checked it: save record</Button>
                            <Button type="button" variant="outline" @click="router.post(`/staff/ocr/${draft.id}/reject`)">Discard</Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
