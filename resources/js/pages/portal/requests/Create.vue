<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Option } from '@/types/records';

const props = defineProps<{ students: { id: number; name: string }[]; types: Option[] }>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'My requests', href: '/my-requests' }] },
});

const form = useForm({
    student_id: props.students[0]?.id ?? '',
    document_type: 'form_137',
    purpose: '',
    copies: 1,
});

const studentOptions = props.students.map((s) => ({ value: s.id, label: s.name }));
const copyOptions = [1, 2, 3, 4, 5].map((n) => ({ value: n, label: String(n) }));
</script>

<template>
    <Head title="New request" />

    <div class="max-w-xl p-4">
        <h1 class="mb-4 text-2xl font-semibold">Request a document</h1>

        <p v-if="students.length === 0" class="border p-4 text-sm">
            You need an approved student record first.
            <Link href="/my-records" class="underline">Link a record</Link> and wait for the registrar to approve it.
        </p>

        <form v-else class="grid gap-4" @submit.prevent="form.post('/my-requests')">
            <div class="grid gap-2">
                <Label>Learner</Label>
                <NativeSelect v-model="form.student_id" :options="studentOptions" />
                <InputError :message="form.errors.student_id" />
            </div>
            <div class="grid gap-2">
                <Label>Document</Label>
                <NativeSelect v-model="form.document_type" :options="types" />
                <InputError :message="form.errors.document_type" />
            </div>
            <div class="grid gap-2">
                <Label for="purpose">Purpose</Label>
                <Input id="purpose" v-model="form.purpose" placeholder="For example: transfer to another school" required />
                <InputError :message="form.errors.purpose" />
            </div>
            <div class="grid gap-2">
                <Label>Copies</Label>
                <NativeSelect v-model="form.copies" :options="copyOptions" />
                <InputError :message="form.errors.copies" />
            </div>
            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">Send request</Button>
                <Button variant="outline" as-child><Link href="/my-requests">Cancel</Link></Button>
            </div>
        </form>
    </div>
</template>
