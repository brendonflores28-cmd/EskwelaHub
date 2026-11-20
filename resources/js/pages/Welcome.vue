<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CircleCheck, ClipboardList, FileText, GraduationCap, Mail, MapPin, Phone, QrCode, UserPlus } from '@lucide/vue';
import { dashboard, login, register } from '@/routes';

/**
 * Home page for visitors. School details come from config/eskwelahub.php (shared
 * as page.props.school) so the school can correct them without touching code.
 */
const page = usePage();
const school = page.props.school;

const services = [
    { icon: UserPlus, title: 'Online enrollment', text: 'New Grade 7 students and transferees fill in one form and upload their documents. No need to line up first.' },
    { icon: FileText, title: 'Request school records', text: 'Ask for Form 137, a certificate of graduation or a certificate of enrollment, and follow every step of your request.' },
    { icon: QrCode, title: 'Genuine documents', text: 'Every document has a QR code. Anyone can scan it to check that the school really issued it.' },
    { icon: ClipboardList, title: 'Your records, private', text: 'Parents and learners see only their own records, after the registrar confirms who they are.' },
];

const steps = [
    { n: 1, title: 'Create an account', text: 'Register with your email address.' },
    { n: 2, title: 'Link the learner', text: 'Enter the learner\'s LRN and birthdate. The registrar confirms the link.' },
    { n: 3, title: 'Enroll or request', text: 'Send your form or request and get an email at every step.' },
];
</script>

<template>
    <Head title="Welcome">
        <meta name="description" :content="`${school.name}: online enrollment and school records, ${school.address}.`" />
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <!-- Top bar -->
        <header class="border-b bg-background/80 backdrop-blur">
            <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
                <Link href="/" class="flex items-center gap-3">
                    <img src="/images/cnhs-logo-96.png" srcset="/images/cnhs-logo-96.png 1x, /images/cnhs-logo-256.png 2x" alt="" class="size-10 rounded-full" />
                    <span class="leading-tight">
                        <span class="block text-sm font-semibold">{{ page.props.name }}</span>
                        <span class="block text-xs text-muted-foreground">{{ school.name }}</span>
                    </span>
                </Link>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="login()" class="rounded-md px-4 py-2 text-sm font-medium hover:bg-muted">Log in</Link>
                        <Link :href="register()" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90">
                            Register
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- Hero: the colours of the school seal (green, with the sun's yellow and orange) -->
        <section class="relative overflow-hidden bg-gradient-to-br from-[#0b8a1c] via-[#0f6b25] to-[#14532d] text-white">
            <div class="absolute -top-24 -right-24 size-96 rounded-full bg-[#f9d71c]/20 blur-3xl"></div>
            <div class="absolute -bottom-32 -left-16 size-96 rounded-full bg-[#f59e0b]/15 blur-3xl"></div>
            <div class="relative mx-auto grid max-w-6xl items-center gap-10 px-4 py-14 md:grid-cols-[1.3fr_1fr] md:py-20">
                <div>
                    <p class="mb-3 inline-block rounded-full bg-white/15 px-3 py-1 text-xs font-medium tracking-wide uppercase">
                        {{ school.district }} - Department of Education
                    </p>
                    <h1 class="text-3xl leading-tight font-bold md:text-5xl">
                        {{ school.name }}
                        <span class="block text-[#f9d71c]">Online Portal</span>
                    </h1>
                    <p class="mt-4 max-w-xl text-base text-white/90 md:text-lg">
                        Enroll online, request your school records and follow every step from your phone or computer.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link :href="page.props.auth.user ? '/my-enrollments/create' : register()" class="rounded-md bg-[#f9d71c] px-5 py-2.5 text-sm font-semibold text-[#14532d] hover:bg-[#f5c800]">
                            Enroll online
                        </Link>
                        <Link :href="page.props.auth.user ? '/my-requests/create' : login()" class="rounded-md border border-white/60 px-5 py-2.5 text-sm font-semibold hover:bg-white/10">
                            Request a document
                        </Link>
                    </div>
                    <p class="mt-6 text-sm text-white/80">Need help? Tap the round chat button at the bottom right.</p>
                </div>
                <div class="flex justify-center">
                    <img
                        src="/images/cnhs-logo.png"
                        :alt="`Official seal of ${school.name}`"
                        class="size-56 rounded-full bg-white p-1 shadow-2xl ring-4 ring-white/30 md:size-72"
                    />
                </div>
            </div>
        </section>

        <!-- What you can do -->
        <section class="mx-auto max-w-6xl px-4 py-14">
            <h2 class="text-2xl font-semibold">What you can do here</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article v-for="s in services" :key="s.title" class="rounded-xl border p-5">
                    <div class="mb-3 flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <component :is="s.icon" class="size-5" />
                    </div>
                    <h3 class="font-medium">{{ s.title }}</h3>
                    <p class="mt-1 text-sm text-muted-foreground">{{ s.text }}</p>
                </article>
            </div>
        </section>

        <!-- How it works -->
        <section class="bg-muted/40">
            <div class="mx-auto max-w-6xl px-4 py-14">
                <h2 class="text-2xl font-semibold">How it works</h2>
                <ol class="mt-6 grid gap-4 md:grid-cols-3">
                    <li v-for="s in steps" :key="s.n" class="flex gap-4 rounded-xl border bg-background p-5">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary font-semibold text-primary-foreground">{{ s.n }}</span>
                        <div>
                            <h3 class="font-medium">{{ s.title }}</h3>
                            <p class="mt-1 text-sm text-muted-foreground">{{ s.text }}</p>
                        </div>
                    </li>
                </ol>
                <p class="mt-6 flex items-center gap-2 text-sm text-muted-foreground">
                    <GraduationCap class="size-4" /> The registrar and the principal log in with two-step verification.
                </p>
            </div>
        </section>

        <!-- Contact -->
        <section class="mx-auto max-w-6xl px-4 py-14">
            <h2 class="text-2xl font-semibold">Contact the school</h2>
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="flex gap-3 rounded-xl border p-5">
                    <MapPin class="mt-0.5 size-5 shrink-0 text-primary" />
                    <div class="text-sm">
                        <div class="font-medium">Address</div>
                        <div class="text-muted-foreground">{{ school.name }}<br />{{ school.address }}</div>
                    </div>
                </div>
                <div class="flex gap-3 rounded-xl border p-5">
                    <Phone class="mt-0.5 size-5 shrink-0 text-primary" />
                    <div class="text-sm">
                        <div class="font-medium">Mobile</div>
                        <a :href="`tel:${school.phone.replace(/\s/g, '')}`" class="text-muted-foreground hover:underline">{{ school.phone }}</a>
                    </div>
                </div>
                <div class="flex gap-3 rounded-xl border p-5">
                    <Mail class="mt-0.5 size-5 shrink-0 text-primary" />
                    <div class="text-sm">
                        <div class="font-medium">Email</div>
                        <a :href="`mailto:${school.email}`" class="text-muted-foreground hover:underline">{{ school.email }}</a>
                        <div class="mt-1">
                            <a :href="school.facebook" target="_blank" rel="noopener" class="text-primary hover:underline">Official Facebook page</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer class="border-t">
            <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-6 text-xs text-muted-foreground md:flex-row md:items-center md:justify-between">
                <p class="flex items-center gap-2">
                    <CircleCheck class="size-4 text-primary" />
                    {{ school.name }} - {{ school.district }} - School ID {{ school.school_id }}
                </p>
                <p>Established by {{ school.established_by }}. Your information is handled under the Data Privacy Act (RA 10173).</p>
            </div>
        </footer>
    </div>
</template>
