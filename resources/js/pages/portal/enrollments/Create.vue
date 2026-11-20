<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Option, StrandOption } from '@/types/records';

defineProps<{ schoolYear: string | null; types: Option[]; strands: StrandOption[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'My enrollment', href: '/my-enrollments' }] },
});

const form = useForm({
    type: 'grade7',
    grade_level: 7,
    strand: '' as string,
    relationship: 'parent',
    lrn: '',
    last_name: '',
    first_name: '',
    middle_name: '',
    suffix: '',
    sex: 'M',
    birthdate: '',
    birthplace: '',
    address: '',
    guardian_name: '',
    guardian_contact: '',
    previous_school: '',
    psa_birth_certificate: null as File | null,
    report_card: null as File | null,
    good_moral: null as File | null,
    other_document: null as File | null,
});

const isTransferee = computed(() => form.type === 'transferee');
// Senior high (Grades 11 and 12) learners choose a strand.
const needsStrand = computed(() => isTransferee.value && Number(form.grade_level) >= 11);
const grades = [7, 8, 9, 10, 11, 12].map((g) => ({ value: g, label: `Grade ${g}` }));

function pick(field: 'psa_birth_certificate' | 'report_card' | 'good_moral' | 'other_document', event: Event): void {
    form[field] = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function submit(): void {
    form.post('/my-enrollments', { forceFormData: true });
}
</script>

<template>
    <Head title="Enrollment form" />

    <div class="max-w-3xl p-4">
        <h1 class="text-2xl font-semibold">Online enrollment form</h1>
        <p class="mb-4 text-sm text-muted-foreground">
            School year {{ schoolYear ?? '(not open yet)' }}
        </p>

        <p v-if="!schoolYear" class="border p-4 text-sm">Enrollment is not open right now.</p>

        <form v-else data-tour="enroll-form" class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label>Type</Label>
                <NativeSelect v-model="form.type" :options="types" />
                <InputError :message="form.errors.type" />
            </div>
            <div v-if="isTransferee" class="grid gap-2">
                <Label>Grade to enter</Label>
                <NativeSelect v-model="form.grade_level" :options="grades" />
                <InputError :message="form.errors.grade_level" />
            </div>
            <div v-if="needsStrand" class="grid gap-2 md:col-span-2">
                <Label>Strand (senior high)</Label>
                <NativeSelect v-model="form.strand" :options="strands.map((s) => ({ value: s.value, label: s.label }))" placeholder="Choose a strand" />
                <InputError :message="form.errors.strand" />
            </div>
            <div v-if="isTransferee" class="grid gap-2 md:col-span-2">
                <Label for="previous_school">Previous school</Label>
                <Input id="previous_school" v-model="form.previous_school" />
                <InputError :message="form.errors.previous_school" />
            </div>

            <h2 class="mt-2 text-lg font-medium md:col-span-2">Learner</h2>
            <div class="grid gap-2">
                <Label for="last_name">Last name</Label>
                <Input id="last_name" v-model="form.last_name" required />
                <InputError :message="form.errors.last_name" />
            </div>
            <div class="grid gap-2">
                <Label for="first_name">First name</Label>
                <Input id="first_name" v-model="form.first_name" required />
                <InputError :message="form.errors.first_name" />
            </div>
            <div class="grid gap-2">
                <Label for="middle_name">Middle name</Label>
                <Input id="middle_name" v-model="form.middle_name" />
            </div>
            <div class="grid gap-2">
                <Label for="suffix">Suffix</Label>
                <Input id="suffix" v-model="form.suffix" maxlength="10" />
            </div>
            <div class="grid gap-2">
                <Label>Sex</Label>
                <NativeSelect v-model="form.sex" :options="[{ value: 'M', label: 'Male' }, { value: 'F', label: 'Female' }]" />
            </div>
            <div class="grid gap-2">
                <Label for="birthdate">Birthdate</Label>
                <Input id="birthdate" v-model="form.birthdate" type="date" required />
                <InputError :message="form.errors.birthdate" />
            </div>
            <div class="grid gap-2">
                <Label for="birthplace">Birthplace</Label>
                <Input id="birthplace" v-model="form.birthplace" />
            </div>
            <div class="grid gap-2">
                <Label for="lrn">LRN (if the learner has one)</Label>
                <Input id="lrn" v-model="form.lrn" inputmode="numeric" maxlength="12" />
                <InputError :message="form.errors.lrn" />
            </div>
            <div class="grid gap-2 md:col-span-2">
                <Label for="address">Home address</Label>
                <Input id="address" v-model="form.address" required />
                <InputError :message="form.errors.address" />
            </div>

            <h2 class="mt-2 text-lg font-medium md:col-span-2">Parent or guardian</h2>
            <div class="grid gap-2">
                <Label for="guardian_name">Full name</Label>
                <Input id="guardian_name" v-model="form.guardian_name" required />
                <InputError :message="form.errors.guardian_name" />
            </div>
            <div class="grid gap-2">
                <Label for="guardian_contact">Mobile number</Label>
                <Input id="guardian_contact" v-model="form.guardian_contact" inputmode="tel" required />
                <InputError :message="form.errors.guardian_contact" />
            </div>
            <div class="grid gap-2">
                <Label>I am the</Label>
                <NativeSelect
                    v-model="form.relationship"
                    :options="[
                        { value: 'parent', label: 'Parent' },
                        { value: 'guardian', label: 'Guardian' },
                        { value: 'self', label: 'Learner' },
                    ]"
                />
            </div>

            <h2 class="mt-2 text-lg font-medium md:col-span-2">Documents (JPG, PNG or PDF)</h2>
            <div class="grid gap-2">
                <Label for="psa">PSA birth certificate</Label>
                <input id="psa" type="file" accept=".jpg,.jpeg,.png,.pdf" required @change="pick('psa_birth_certificate', $event)" />
                <InputError :message="form.errors.psa_birth_certificate" />
            </div>
            <div class="grid gap-2">
                <Label for="card">Report card (Form 138)</Label>
                <input id="card" type="file" accept=".jpg,.jpeg,.png,.pdf" required @change="pick('report_card', $event)" />
                <InputError :message="form.errors.report_card" />
            </div>
            <div v-if="isTransferee" class="grid gap-2">
                <Label for="gm">Certificate of good moral character</Label>
                <input id="gm" type="file" accept=".jpg,.jpeg,.png,.pdf" required @change="pick('good_moral', $event)" />
                <InputError :message="form.errors.good_moral" />
            </div>
            <div class="grid gap-2">
                <Label for="other">Other document (optional)</Label>
                <input id="other" type="file" accept=".jpg,.jpeg,.png,.pdf" @change="pick('other_document', $event)" />
                <InputError :message="form.errors.other_document" />
            </div>

            <div class="flex gap-2 md:col-span-2">
                <Button type="submit" :disabled="form.processing">Send form</Button>
                <Button variant="outline" as-child><Link href="/my-enrollments">Cancel</Link></Button>
            </div>
        </form>
    </div>
</template>
