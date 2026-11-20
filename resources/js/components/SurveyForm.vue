<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import NativeSelect from '@/components/NativeSelect.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';

/** Short satisfaction survey shown once after a transaction is finished. */
const props = defineProps<{ action: string }>();

const form = useForm({ rating_overall: 5, rating_speed: 5, rating_staff: 5, comment: '' });

const stars = [
    { value: 5, label: '5 - Very satisfied' },
    { value: 4, label: '4 - Satisfied' },
    { value: 3, label: '3 - Okay' },
    { value: 2, label: '2 - Not satisfied' },
    { value: 1, label: '1 - Very unsatisfied' },
];
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>How was our service?</CardTitle>
            <CardDescription>Your answers help the school improve. You can write in English or Tagalog.</CardDescription>
        </CardHeader>
        <CardContent>
            <form class="grid max-w-xl gap-3" @submit.prevent="form.post(props.action, { preserveScroll: true })">
                <div class="grid gap-1">
                    <Label>Overall</Label>
                    <NativeSelect v-model="form.rating_overall" :options="stars" />
                </div>
                <div class="grid gap-1">
                    <Label>Speed of service</Label>
                    <NativeSelect v-model="form.rating_speed" :options="stars" />
                </div>
                <div class="grid gap-1">
                    <Label>Staff courtesy</Label>
                    <NativeSelect v-model="form.rating_staff" :options="stars" />
                </div>
                <div class="grid gap-1">
                    <Label for="comment">Comment (optional)</Label>
                    <textarea
                        id="comment"
                        v-model="form.comment"
                        rows="3"
                        maxlength="1000"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    ></textarea>
                    <InputError :message="form.errors.comment || form.errors.rating_overall" />
                </div>
                <div><Button type="submit" :disabled="form.processing">Send feedback</Button></div>
            </form>
        </CardContent>
    </Card>
</template>
