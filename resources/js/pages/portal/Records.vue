<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineProps<{
    approved: { student_id: number; full_name: string; lrn: string | null; relationship: string }[];
    pending: number;
    rejected: number;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'My records', href: '/my-records' }] },
});

const form = useForm({ lrn: '', birthdate: '', relationship: 'self' });

function submit(): void {
    form.post('/my-records/link', { preserveScroll: true, onSuccess: () => form.reset('lrn', 'birthdate') });
}
</script>

<template>
    <Head title="My records" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-semibold">My records</h1>

        <Card>
            <CardHeader>
                <CardTitle>Linked student records</CardTitle>
                <CardDescription v-if="pending > 0">
                    {{ pending }} request(s) waiting for the registrar.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ul v-if="approved.length" class="divide-y">
                    <li v-for="a in approved" :key="a.student_id" class="flex items-center justify-between py-2">
                        <div>
                            <div class="font-medium">{{ a.full_name }}</div>
                            <div class="text-sm text-muted-foreground capitalize">
                                {{ a.relationship }} - LRN {{ a.lrn ?? 'not assigned' }}
                            </div>
                        </div>
                        <Button variant="outline" size="sm" as-child>
                            <Link :href="`/my-records/${a.student_id}`">Open</Link>
                        </Button>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">No linked records yet.</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Link a student record</CardTitle>
            </CardHeader>
            <CardContent>
                <form class="grid max-w-xl gap-3 md:grid-cols-3" @submit.prevent="submit">
                    <div class="grid gap-1">
                        <Label for="lrn">LRN</Label>
                        <Input id="lrn" v-model="form.lrn" inputmode="numeric" maxlength="12" required />
                    </div>
                    <div class="grid gap-1">
                        <Label for="birthdate">Learner's birthdate</Label>
                        <Input id="birthdate" v-model="form.birthdate" type="date" required />
                    </div>
                    <div class="grid gap-1">
                        <Label>I am the</Label>
                        <NativeSelect
                            v-model="form.relationship"
                            :options="[
                                { value: 'self', label: 'Learner / alumnus' },
                                { value: 'parent', label: 'Parent' },
                                { value: 'guardian', label: 'Guardian' },
                            ]"
                        />
                    </div>
                    <div class="md:col-span-3">
                        <InputError :message="form.errors.lrn || form.errors.birthdate" />
                        <Button type="submit" class="mt-2" :disabled="form.processing">Send request</Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
