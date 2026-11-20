<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CircleCheck, CircleX } from '@lucide/vue';

/** Opened from the QR code on a printed document. Shows no personal data. */
defineProps<{
    valid: boolean;
    document: { request_no: string; type: string; issued_on: string; learner: string } | null;
    school: string;
}>();
</script>

<template>
    <Head title="Verify document" />

    <main class="mx-auto flex min-h-screen max-w-md flex-col justify-center gap-4 p-6">
        <p class="text-center text-sm text-muted-foreground">{{ school }}</p>

        <div v-if="valid && document" class="border border-green-600/40 bg-green-50 p-6 text-center dark:bg-green-950/30">
            <CircleCheck class="mx-auto size-12 text-green-600" />
            <h1 class="mt-2 text-xl font-semibold">This document is genuine</h1>
            <dl class="mt-4 space-y-1 text-left text-sm">
                <div class="flex justify-between"><dt class="text-muted-foreground">Request no.</dt><dd>{{ document.request_no }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted-foreground">Document</dt><dd>{{ document.type }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted-foreground">Learner</dt><dd>{{ document.learner }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted-foreground">Issued</dt><dd>{{ document.issued_on }}</dd></div>
            </dl>
            <p class="mt-4 text-xs text-muted-foreground">
                Compare these details with the printed copy. The learner's name is hidden on purpose.
            </p>
        </div>

        <div v-else class="border border-red-600/40 bg-red-50 p-6 text-center dark:bg-red-950/30">
            <CircleX class="mx-auto size-12 text-red-600" />
            <h1 class="mt-2 text-xl font-semibold">Document not found</h1>
            <p class="mt-2 text-sm text-muted-foreground">
                This code is not valid, or the document was not issued. Please contact the school registrar.
            </p>
        </div>
    </main>
</template>
