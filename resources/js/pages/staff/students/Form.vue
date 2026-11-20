<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Option, Student } from '@/types/records';

/** Create (student is null) or edit the identity data of one student. */
const props = defineProps<{ student: { data: Student } | null; statuses: Option[] }>();

const record = computed(() => props.student?.data ?? null);
const editing = computed(() => record.value !== null);

defineOptions({
    layout: { breadcrumbs: [{ title: 'Student records', href: '/staff/students' }] },
});

const form = useForm({
    lrn: record.value?.lrn ?? '',
    last_name: record.value?.last_name ?? '',
    first_name: record.value?.first_name ?? '',
    middle_name: record.value?.middle_name ?? '',
    suffix: record.value?.suffix ?? '',
    sex: record.value?.sex ?? 'M',
    birthdate: record.value?.birthdate ?? '',
    birthplace: record.value?.birthplace ?? '',
    status: record.value?.status ?? 'enrolled',
});

function submit(): void {
    if (record.value) {
        form.put(`/staff/students/${record.value.id}`);
    } else {
        form.post('/staff/students');
    }
}
</script>

<template>
    <Head :title="editing ? 'Edit student record' : 'New student record'" />

    <div class="max-w-2xl p-4">
        <h1 class="mb-4 text-2xl font-semibold">
            {{ editing ? 'Edit student record' : 'New student record' }}
        </h1>

        <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
            <div class="grid gap-2 md:col-span-2">
                <Label for="lrn">LRN (12 digits, leave empty for old records)</Label>
                <Input id="lrn" v-model="form.lrn" inputmode="numeric" maxlength="12" />
                <InputError :message="form.errors.lrn" />
            </div>

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
                <InputError :message="form.errors.middle_name" />
            </div>
            <div class="grid gap-2">
                <Label for="suffix">Suffix (Jr., III)</Label>
                <Input id="suffix" v-model="form.suffix" maxlength="10" />
                <InputError :message="form.errors.suffix" />
            </div>

            <div class="grid gap-2">
                <Label for="sex">Sex</Label>
                <NativeSelect
                    v-model="form.sex"
                    :options="[
                        { value: 'M', label: 'Male' },
                        { value: 'F', label: 'Female' },
                    ]"
                />
                <InputError :message="form.errors.sex" />
            </div>
            <div class="grid gap-2">
                <Label for="birthdate">Birthdate</Label>
                <Input id="birthdate" v-model="form.birthdate" type="date" required />
                <InputError :message="form.errors.birthdate" />
            </div>

            <div class="grid gap-2">
                <Label for="birthplace">Birthplace</Label>
                <Input id="birthplace" v-model="form.birthplace" />
                <InputError :message="form.errors.birthplace" />
            </div>
            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <NativeSelect v-model="form.status" :options="statuses" />
                <InputError :message="form.errors.status" />
            </div>

            <div class="flex gap-2 md:col-span-2">
                <Button type="submit" :disabled="form.processing">Save record</Button>
                <Button variant="outline" as-child>
                    <Link :href="record ? `/staff/students/${record.id}` : '/staff/students'">Cancel</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
