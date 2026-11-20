<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    engine: { name: string; runsOnServer: boolean };
    maxKb: number;
}>();

defineOptions({
    layout: { breadcrumbs: [{ title: 'Digitize records', href: '/staff/ocr' }] },
});

const form = useForm({ scan: null as File | null, text: '' });
const status = ref('');
const progress = ref(0);
const reading = ref(false);

/**
 * Default engine: Tesseract.js reads the image inside this browser, so the
 * scan is not sent to any third party. The text goes to the server together
 * with the file. PDFs are not read in the browser: upload an image instead,
 * or type the record by hand on the next screen.
 */
async function readInBrowser(file: File): Promise<string> {
    const { createWorker } = await import('tesseract.js');
    const worker = await createWorker('eng', 1, {
        logger: (m: { status: string; progress: number }) => {
            status.value = m.status;
            progress.value = Math.round(m.progress * 100);
        },
    });

    try {
        const { data } = await worker.recognize(file);

        return data.text;
    } finally {
        await worker.terminate();
    }
}

async function pick(event: Event): Promise<void> {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.scan = file;
    form.text = '';

    if (file && !props.engine.runsOnServer && file.type.startsWith('image/')) {
        reading.value = true;
        try {
            form.text = await readInBrowser(file);
            status.value = 'done';
        } catch {
            status.value = 'Could not read the image in the browser. You can still continue and type the record by hand.';
        } finally {
            reading.value = false;
        }
    }
}

function submit(): void {
    form.post('/staff/ocr', { forceFormData: true });
}
</script>

<template>
    <Head title="Upload a scan" />

    <div class="max-w-xl p-4">
        <h1 class="text-2xl font-semibold">Upload a scanned record</h1>
        <p class="mb-4 text-sm text-muted-foreground">
            The text is read automatically ({{ engine.runsOnServer ? 'on the server by ' + engine.name : "in your browser, nothing is sent to a third party" }}).
            You check every field before a record is created. Photos and PDFs up to {{ Math.round(maxKb / 1024) }} MB.
        </p>

        <form class="grid gap-4" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="scan">Scan (JPG, PNG or PDF)</Label>
                <input id="scan" type="file" accept=".jpg,.jpeg,.png,.pdf" required @change="pick" />
                <InputError :message="form.errors.scan" />
            </div>

            <div v-if="reading" class="text-sm">
                Reading the image: {{ status }} {{ progress }}%
                <div class="mt-1 h-2 w-full overflow-hidden bg-muted">
                    <div class="h-full bg-primary transition-all" :style="{ width: progress + '%' }"></div>
                </div>
                <p class="mt-1 text-muted-foreground">The first scan downloads the language data and can take a minute.</p>
            </div>
            <p v-else-if="status && status !== 'done'" class="text-sm text-amber-600">{{ status }}</p>
            <p v-else-if="status === 'done'" class="text-sm text-green-600">Text found. Continue to check the fields.</p>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing || reading || !form.scan">Continue</Button>
                <Button variant="outline" as-child><Link href="/staff/ocr">Cancel</Link></Button>
            </div>
        </form>
    </div>
</template>
