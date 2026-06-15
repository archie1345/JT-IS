<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowRight,
    Building2,
    CheckCircle2,
    Mail,
    MapPin,
    Phone,
    Waves,
    Zap,
} from 'lucide-vue-next';
import { dashboard, login } from '@/routes';
import type { Auth } from '@/types';

type WelcomePageProps = {
    auth?: Partial<Auth>;
};

const page = usePage<WelcomePageProps>();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const internalHref = computed(() =>
    isAuthenticated.value ? dashboard() : login(),
);

const navItems = [
    { label: 'Profil', href: '#profil' },
    { label: 'Produk', href: '#produk' },
    { label: 'Proyek', href: '#project' },
    { label: 'Kontak', href: '#kontak' },
];

const products = [
    'Steel Sheet Pile',
    'Jasa Konstruksi',
    'Sewa Peralatan',
    'Solar Panel',
    'Telemetri',
    'Proyek EPC',
];

const portfolioItems = [
    'Perkuatan tanggul dan check dam Muara Sungai Mandosi',
    'Pekerjaan SPAM Jatiluhur',
    'PLTM Lodagung 2 x 650 kW',
    'Telemetri AWLR, ARR, EWS, WQMS, dan ADCP',
];

const strengths = [
    {
        icon: Waves,
        title: 'Sumber Daya Air',
        description:
            'Berpengalaman dalam pekerjaan sarana, prasarana, dan konstruksi bidang sumber daya air.',
    },
    {
        icon: Zap,
        title: 'Energi Terbarukan',
        description:
            'Mendukung layanan PLTS, PJU tenaga surya, dan pembangkit listrik mini hidro.',
    },
    {
        icon: Building2,
        title: 'Konstruksi & Peralatan',
        description:
            'Menyediakan pekerjaan konstruksi, steel sheet pile, sewa alat berat, dan solusi design-build.',
    },
];
</script>

<template>
    <Head title="Jasa Tirta Energi" />

    <main class="min-h-screen bg-[#f4f8f4] text-[#17342d]">
        <section
            class="relative flex min-h-[92vh] overflow-hidden bg-[#0b342d] text-white"
        >
            <img
                src="/assets/images/MANDOSI-2-scaled.jpeg"
                alt="Pekerjaan infrastruktur Jasa Tirta Energi"
                class="absolute inset-0 h-full w-full object-cover"
            />
            <div class="absolute inset-0 bg-[#09211e]/80" />

            <div
                class="relative z-10 flex w-full flex-col px-5 sm:px-8 lg:px-12"
            >
                <header
                    class="flex min-w-0 items-center justify-between gap-4 py-5"
                >
                    <Link href="/" class="min-w-0">
                        <img
                            src="/assets/svg/JTE_Logo.svg"
                            alt="Jasa Tirta Energi"
                            class="h-16 w-auto "
                        />
                    </Link>

                    <nav
                        class="hidden items-center gap-6 text-sm font-medium text-white/80 lg:flex"
                    >
                        <a
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            class="transition hover:text-[#f6c84c]"
                        >
                            {{ item.label }}
                        </a>
                    </nav>

                    <Link
                        :href="internalHref"
                        class="inline-flex shrink-0 items-center gap-2 rounded-md bg-white px-4 py-2 text-sm font-semibold text-[#17342d] shadow-sm transition hover:bg-[#eef6f1]"
                    >
                        Area Internal
                        <ArrowRight class="size-4" />
                    </Link>
                </header>

                <div
                    class="grid flex-1 items-end gap-10 pt-16 pb-12 lg:grid-cols-[minmax(0,1fr)_24rem] lg:pt-24 lg:pb-16"
                >
                    <div class="max-w-3xl">
                        <p
                            class="text-sm font-semibold tracking-[0.24em] text-[#f6c84c] uppercase"
                        >
                            PT Jasa Tirta Energi
                        </p>
                        <h1
                            class="mt-4 max-w-3xl text-4xl leading-tight font-semibold sm:text-6xl"
                        >
                            Partner konstruksi, peralatan, dan energi
                            terbarukan.
                        </h1>
                        <p
                            class="mt-5 max-w-2xl text-base leading-7 text-white/80"
                        >
                            JTE adalah anak perusahaan Perum Jasa Tirta I yang
                            bergerak di bidang konstruksi, pengelolaan sumber
                            daya air, penyediaan peralatan, telemetri, dan
                            solusi energi baru terbarukan.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <a
                                href="mailto:corporate@jasatirtaenergi.co.id"
                                class="inline-flex items-center gap-2 rounded-md bg-[#f6c84c] px-5 py-3 text-sm font-semibold text-[#17342d] shadow-sm transition hover:bg-[#ffd966]"
                            >
                                Email Kami
                                <Mail class="size-4" />
                            </a>
                            <Link
                                :href="internalHref"
                                class="inline-flex items-center gap-2 rounded-md border border-white/40 bg-white/10 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20"
                            >
                                Area Internal
                                <ArrowRight class="size-4" />
                            </Link>
                        </div>
                    </div>

                    <div
                        class="rounded-lg border border-white/20 bg-white/10 p-5 backdrop-blur"
                    >
                        <p class="text-sm font-semibold text-[#f6c84c]">
                            Your Best Partner
                        </p>
                        <p class="mt-3 text-sm leading-6 text-white/80">
                            Layanan JTE mengutamakan mutu, biaya, dan waktu
                            untuk mendukung kebutuhan mitra di berbagai sektor.
                        </p>
                        <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-md bg-white/10 p-3">
                                <p class="text-2xl font-semibold">200+</p>
                                <p class="text-white/70">Mitra</p>
                            </div>
                            <div class="rounded-md bg-white/10 p-3">
                                <p class="text-2xl font-semibold">1000+</p>
                                <p class="text-white/70">Proyek</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="profil" class="border-y border-[#d5e3dc] bg-white">
            <div
                class="mx-auto grid max-w-6xl gap-6 px-5 py-12 sm:px-8 lg:grid-cols-[0.9fr_1.1fr] lg:px-12"
            >
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.22em] text-[#0f766e] uppercase"
                    >
                        Selamat datang
                    </p>
                    <h2 class="mt-3 text-3xl font-semibold text-[#17342d]">
                        Jasa Tirta Energi
                    </h2>
                </div>
                <p class="text-sm leading-7 text-[#546a62]">
                    PT Jasa Tirta Energi menyediakan layanan konstruksi sumber
                    daya air, pekerjaan tanah, pengerukan, konstruksi gedung,
                    steel sheet pile, sewa alat berat, telemetri, PLTS rooftop,
                    penerangan jalan tenaga surya, dan pembangkit listrik mini
                    hidro.
                </p>
            </div>
        </section>

        <section
            id="produk"
            class="mx-auto max-w-6xl px-5 py-14 sm:px-8 lg:px-12"
        >
            <div class="max-w-2xl">
                <p
                    class="text-xs font-semibold tracking-[0.22em] text-[#0f766e] uppercase"
                >
                    Produk dan layanan
                </p>
                <h2 class="mt-3 text-2xl font-semibold text-[#17342d]">
                    Solusi yang menyesuaikan kebutuhan pekerjaan
                </h2>
            </div>

            <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="product in products"
                    :key="product"
                    class="flex min-w-0 items-center gap-3 rounded-lg border border-[#d5e3dc] bg-white p-4 shadow-sm"
                >
                    <CheckCircle2 class="size-5 shrink-0 text-[#0f766e]" />
                    <span class="text-sm font-semibold text-[#17342d]">
                        {{ product }}
                    </span>
                </div>
            </div>
        </section>

        <section class="bg-[#103d35] py-14 text-white">
            <div
                class="mx-auto grid max-w-6xl gap-4 px-5 sm:px-8 lg:grid-cols-3 lg:px-12"
            >
                <article
                    v-for="strength in strengths"
                    :key="strength.title"
                    class="rounded-lg border border-white/15 bg-white/10 p-5"
                >
                    <component
                        :is="strength.icon"
                        class="size-7 text-[#f6c84c]"
                    />
                    <h3 class="mt-4 text-base font-semibold">
                        {{ strength.title }}
                    </h3>
                    <p class="mt-3 text-sm leading-6 text-white/75">
                        {{ strength.description }}
                    </p>
                </article>
            </div>
        </section>

        <section
            id="project"
            class="mx-auto max-w-6xl px-5 py-14 sm:px-8 lg:px-12"
        >
            <div class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr] lg:items-start">
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.22em] text-[#0f766e] uppercase"
                    >
                        Portofolio
                    </p>
                    <h2 class="mt-3 text-2xl font-semibold text-[#17342d]">
                        Pekerjaan JTE di berbagai bidang
                    </h2>
                    <p class="mt-4 text-sm leading-7 text-[#546a62]">
                        Portofolio JTE mencakup pekerjaan konstruksi, penguatan
                        infrastruktur air, pembangkit, telemetri, dan layanan
                        peralatan pendukung proyek.
                    </p>
                </div>

                <div class="grid gap-3">
                    <div
                        v-for="item in portfolioItems"
                        :key="item"
                        class="rounded-lg border border-[#d5e3dc] bg-white p-4 text-sm font-medium text-[#17342d] shadow-sm"
                    >
                        {{ item }}
                    </div>
                </div>
            </div>
        </section>

        <section id="kontak" class="border-t border-[#d5e3dc] bg-white">
            <div
                class="mx-auto grid max-w-6xl gap-6 px-5 py-10 sm:px-8 lg:grid-cols-[1fr_auto] lg:items-center lg:px-12"
            >
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.22em] text-[#0f766e] uppercase"
                    >
                        Kontak
                    </p>
                    <h2 class="mt-3 text-2xl font-semibold text-[#17342d]">
                        Konsultasikan kebutuhan Anda bersama JTE
                    </h2>
                    <div class="mt-5 grid gap-2 text-sm text-[#546a62]">
                        <p class="flex items-start gap-2">
                            <MapPin
                                class="mt-0.5 size-4 shrink-0 text-[#0f766e]"
                            />
                            Jl. Bendungan Riam Kanan 15-17 Kota Malang
                        </p>
                        <p class="flex items-center gap-2">
                            <Phone class="size-4 shrink-0 text-[#0f766e]" />
                            0811-3332-9696
                        </p>
                        <p class="flex items-center gap-2">
                            <Mail class="size-4 shrink-0 text-[#0f766e]" />
                            corporate@jasatirtaenergi.co.id
                        </p>
                    </div>
                </div>

                <a
                    href="mailto:corporate@jasatirtaenergi.co.id"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-[#f6c84c] px-5 py-3 text-sm font-semibold text-[#17342d] shadow-sm transition hover:bg-[#ffd966]"
                >
                    Email Kami
                    <Mail class="size-4" />
                </a>
            </div>
        </section>
    </main>
</template>
