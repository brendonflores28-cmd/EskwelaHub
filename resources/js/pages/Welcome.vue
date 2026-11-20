<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ClipboardCheck, FileText, GraduationCap, Mail, MapPin, Phone, QrCode, ShieldCheck, UserPlus } from '@lucide/vue';
import { dashboard, login, register } from '@/routes';

/**
 * Home page. A formal, academic layout: serif headings, a gold rule, and a
 * hero photo covered with transparent school green.
 *
 * HERO PHOTO: put the school's own photo at public/images/school-hero.jpg
 * (a wide landscape picture, 1600 px or wider). It is drawn on top of
 * hero-scenery.jpg (made from the school seal), which shows until the photo exists.
 *
 * School details come from config/eskwelahub.php (page.props.school).
 */
const page = usePage();
const school = page.props.school;

const services = [
    { icon: UserPlus, title: 'Online Enrollment', text: 'New Grade 7 learners and transferees submit one form with their documents. The registrar reviews it and you are told the decision by email.' },
    { icon: FileText, title: 'Records and Certifications', text: 'Request Form 137, a certificate of graduation or a certificate of enrollment, and follow each step of the request.' },
    { icon: QrCode, title: 'Document Verification', text: 'Every document carries a QR code. Anyone can scan it to confirm that the school issued the document.' },
    { icon: ShieldCheck, title: 'Protected Records', text: 'Learner records are private. Parents and learners see only their own records, after the registrar confirms their identity.' },
];

const steps = [
    { n: 'I', title: 'Register an account', text: 'Create an account with your email address.' },
    { n: 'II', title: 'Link the learner', text: 'Enter the learner\'s LRN and birthdate. The registrar confirms the link.' },
    { n: 'III', title: 'Enroll or request', text: 'Send the enrollment form or the document request and follow it online.' },
];
</script>

<template>
    <Head title="Welcome">
        <meta name="description" :content="`${school.name}: online enrollment and school records, ${school.address}.`" />
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <!-- Official strip -->
        <div class="bg-[#0d4a1c] text-[11px] tracking-wide text-white/85 uppercase">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-6 gap-y-1 px-4 py-1.5">
                <span>Republic of the Philippines &middot; Department of Education</span>
                <span>{{ school.district }} &middot; School ID {{ school.school_id }}</span>
            </div>
        </div>
        <div class="h-1 bg-gold"></div>

        <!-- Header -->
        <header class="border-b bg-background">
            <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
                <Link href="/" class="flex items-center gap-3">
                    <img src="/images/cnhs-logo-96.png" srcset="/images/cnhs-logo-96.png 1x, /images/cnhs-logo-256.png 2x" alt="" class="size-12" />
                    <span class="leading-tight">
                        <span class="block font-serif text-lg font-bold">{{ school.name }}</span>
                        <span class="block text-xs tracking-wide text-muted-foreground uppercase">{{ page.props.name }} &middot; School Portal</span>
                    </span>
                </Link>
                <div class="flex items-center gap-1 text-sm">
                    <a href="#services" class="hidden rounded-md px-3 py-2 hover:bg-muted md:block">Services</a>
                    <a href="#process" class="hidden rounded-md px-3 py-2 hover:bg-muted md:block">How it works</a>
                    <a href="#contact" class="hidden rounded-md px-3 py-2 hover:bg-muted md:block">Contact</a>
                    <Link v-if="page.props.auth.user" :href="dashboard()" class="ml-2 rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground hover:opacity-90">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link :href="login()" class="ml-2 rounded-md border px-4 py-2 font-medium hover:bg-muted">Log in</Link>
                        <Link :href="register()" class="rounded-md bg-primary px-4 py-2 font-medium text-primary-foreground hover:opacity-90">Register</Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- Hero: school photo covered with transparent green -->
        <section class="hero relative isolate overflow-hidden text-white">
            <div class="absolute inset-0 -z-20 bg-cover bg-center" style="background-image: url('/images/school-hero.jpg'), url('/images/hero-scenery.jpg')"></div>
            <div class="absolute inset-0 -z-10 bg-gradient-to-r from-[#0a4d1a]/92 via-[#0d6b25]/72 to-[#0d6b25]/38"></div>
            <div class="absolute inset-x-0 bottom-0 -z-10 h-32 bg-gradient-to-t from-[#06330f]/80 to-transparent"></div>

            <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 py-16 md:grid-cols-[1.4fr_1fr] md:py-24">
                <div>
                    <p class="mb-4 flex items-center gap-3 text-xs font-semibold tracking-[0.2em] text-[#f1d36a] uppercase">
                        <span class="h-px w-10 bg-gold"></span> Office of the Registrar
                    </p>
                    <h1 class="font-serif text-4xl leading-tight font-bold md:text-6xl">{{ school.name }}</h1>
                    <p class="mt-3 font-serif text-xl text-white/90 italic md:text-2xl">Online Enrollment and Student Records Portal</p>
                    <p class="mt-5 max-w-xl text-base text-white/85">
                        Enroll your child, request school records and follow every step of your request, from your phone or computer.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link :href="page.props.auth.user ? '/my-enrollments/create' : register()" class="rounded-md bg-gold px-6 py-3 text-sm font-semibold text-[#0a3d14] shadow hover:brightness-105">
                            Enroll Online
                        </Link>
                        <Link :href="page.props.auth.user ? '/my-requests/create' : login()" class="rounded-md border border-white/70 px-6 py-3 text-sm font-semibold hover:bg-white/10">
                            Request a Document
                        </Link>
                    </div>
                </div>
                <div class="flex justify-center md:justify-end">
                    <img
                        src="/images/cnhs-logo.png"
                        :alt="`Official seal of ${school.name}`"
                        class="size-52 drop-shadow-[0_10px_30px_rgba(0,0,0,0.45)] md:size-72"
                    />
                </div>
            </div>
        </section>

        <!-- Services -->
        <section id="services" class="mx-auto max-w-6xl px-4 py-16">
            <div class="mb-8 text-center">
                <p class="text-xs font-semibold tracking-[0.2em] text-primary uppercase">What the portal offers</p>
                <h2 class="mt-2 font-serif text-3xl font-bold">Services for Learners, Parents and Alumni</h2>
                <div class="mx-auto mt-3 h-px w-24 bg-gold"></div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <article v-for="s in services" :key="s.title" class="rounded-sm border border-t-4 border-t-primary bg-card p-6 shadow-sm">
                    <component :is="s.icon" class="mb-4 size-8 text-primary" />
                    <h3 class="font-serif text-lg font-bold">{{ s.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ s.text }}</p>
                </article>
            </div>
        </section>

        <!-- Process -->
        <section id="process" class="border-y bg-muted/40">
            <div class="mx-auto max-w-6xl px-4 py-16">
                <div class="mb-8 text-center">
                    <p class="text-xs font-semibold tracking-[0.2em] text-primary uppercase">Getting started</p>
                    <h2 class="mt-2 font-serif text-3xl font-bold">How It Works</h2>
                    <div class="mx-auto mt-3 h-px w-24 bg-gold"></div>
                </div>
                <ol class="grid gap-5 md:grid-cols-3">
                    <li v-for="s in steps" :key="s.n" class="relative rounded-sm border bg-background p-6">
                        <span class="font-serif text-4xl font-bold text-gold">{{ s.n }}</span>
                        <h3 class="mt-2 font-serif text-lg font-bold">{{ s.title }}</h3>
                        <p class="mt-1 text-sm text-muted-foreground">{{ s.text }}</p>
                    </li>
                </ol>
                <p class="mt-8 flex items-center justify-center gap-2 text-sm text-muted-foreground">
                    <ClipboardCheck class="size-4" /> The registrar and the principal sign in with two-step verification.
                </p>
            </div>
        </section>

        <!-- Contact -->
        <section id="contact" class="mx-auto max-w-6xl px-4 py-16">
            <div class="mb-8 text-center">
                <p class="text-xs font-semibold tracking-[0.2em] text-primary uppercase">Visit or write to us</p>
                <h2 class="mt-2 font-serif text-3xl font-bold">Contact the School</h2>
                <div class="mx-auto mt-3 h-px w-24 bg-gold"></div>
            </div>
            <div class="grid gap-5 md:grid-cols-3">
                <div class="flex gap-4 rounded-sm border bg-card p-6">
                    <MapPin class="mt-1 size-6 shrink-0 text-primary" />
                    <div class="text-sm">
                        <div class="font-serif text-base font-bold">Address</div>
                        <div class="mt-1 text-muted-foreground">{{ school.name }}<br />{{ school.address }}</div>
                    </div>
                </div>
                <div class="flex gap-4 rounded-sm border bg-card p-6">
                    <Phone class="mt-1 size-6 shrink-0 text-primary" />
                    <div class="text-sm">
                        <div class="font-serif text-base font-bold">Mobile</div>
                        <a :href="`tel:${school.phone.replace(/\s/g, '')}`" class="mt-1 block text-muted-foreground hover:underline">{{ school.phone }}</a>
                    </div>
                </div>
                <div class="flex gap-4 rounded-sm border bg-card p-6">
                    <Mail class="mt-1 size-6 shrink-0 text-primary" />
                    <div class="text-sm">
                        <div class="font-serif text-base font-bold">Email</div>
                        <a :href="`mailto:${school.email}`" class="mt-1 block text-muted-foreground hover:underline">{{ school.email }}</a>
                        <a :href="school.facebook" target="_blank" rel="noopener" class="mt-1 block text-primary hover:underline">Official Facebook page</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-[#0d4a1c] text-white/85">
            <div class="h-1 bg-gold"></div>
            <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 md:grid-cols-[auto_1fr_1fr]">
                <img src="/images/cnhs-logo-96.png" srcset="/images/cnhs-logo-96.png 1x, /images/cnhs-logo-256.png 2x" alt="" class="size-16" />
                <div class="text-sm">
                    <div class="font-serif text-lg font-bold text-white">{{ school.name }}</div>
                    <div>{{ school.address }}</div>
                    <div>{{ school.district }} &middot; School ID {{ school.school_id }}</div>
                </div>
                <div class="flex items-start gap-2 text-xs text-white/70">
                    <GraduationCap class="mt-0.5 size-4 shrink-0" />
                    <p>Established by {{ school.established_by }}. Personal information is handled under the Data Privacy Act of 2012 (RA 10173).</p>
                </div>
            </div>
        </footer>
    </div>
</template>
