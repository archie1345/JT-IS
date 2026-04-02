<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Prototype Login" />

    <div class="min-h-screen bg-white text-slate-950">
        <div class="grid min-h-screen md:grid-cols-2">
            <div class="flex min-h-screen items-center justify-center bg-white px-6 py-10 md:px-10 lg:px-16">
                <div class="w-full max-w-xl">
                    <div class="mb-6 flex justify-center">
                        <img
                            src="/assets/svg/JTE_Logo_blk_txt.svg"
                            alt="JTIS Logo"
                            class="h-auto w-[150px]"
                        >
                    </div>

                    <div class="mx-auto w-full max-w-md px-2 sm:px-0">
                        <h1 class="mb-2 text-center text-4xl font-bold tracking-tight text-slate-900">
                            WELCOME TO JTIS
                        </h1>
                        <p class="mb-8 text-justify text-sm leading-6 text-slate-500">
                            Untuk Menggunakan Sistem Ini harap Login Menggunakan Email dan Password Anda
                        </p>

                        <form class="space-y-4" @submit.prevent="submit">
                            <div>
                                <div class="group flex items-center rounded-xl border border-slate-300 bg-white transition-all duration-200 focus-within:border-sky-400 focus-within:bg-[#EBEEFF] focus-within:shadow-[0_0_0_4px_rgba(129,203,255,0.18)]">
                                    <div class="flex shrink-0 items-center pl-4 pr-2 text-slate-900">
                                        <svg class="h-6 w-6" viewBox="0 0 40 33" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M37.8333 5.58333C37.8333 3.6125 36.2208 2 34.25 2H5.58333C3.6125 2 2 3.6125 2 5.58333M37.8333 5.58333V27.0833C37.8333 29.0542 36.2208 30.6667 34.25 30.6667H5.58333C3.6125 30.6667 2 29.0542 2 27.0833V5.58333M37.8333 5.58333L19.9167 18.125L2 5.58333" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div class="relative min-w-0 flex-1">
                                        <input
                                            id="emailInput"
                                            v-model="form.email"
                                            type="email"
                                            autocomplete="email"
                                            placeholder=" "
                                            class="peer h-16 w-full border-0 bg-transparent px-1 pb-3 pt-6 text-base text-slate-900 outline-none"
                                            required
                                        >
                                        <label
                                            for="emailInput"
                                            class="pointer-events-none absolute left-1 top-1/2 -translate-y-1/2 text-sm text-slate-500 transition-all duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:text-base peer-focus:top-4 peer-focus:text-sm peer-not-placeholder-shown:top-4 peer-not-placeholder-shown:text-sm"
                                        >
                                            E-mail
                                        </label>
                                    </div>
                                </div>
                                <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <div class="group flex items-center rounded-xl border border-slate-300 bg-white transition-all duration-200 focus-within:border-sky-400 focus-within:bg-[#EBEEFF] focus-within:shadow-[0_0_0_4px_rgba(129,203,255,0.18)]">
                                    <div class="flex shrink-0 items-center pl-4 pr-2 text-slate-900">
                                        <svg class="h-6 w-6" viewBox="0 0 37 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M9.16667 18.125V10.9583C9.16667 8.58244 10.1105 6.30385 11.7905 4.62384C13.4705 2.94382 15.7491 2 18.125 2C20.5009 2 22.7795 2.94382 24.4595 4.62384C26.1395 6.30385 27.0833 8.58244 27.0833 10.9583V18.125M5.58333 18.125H30.6667C32.6457 18.125 34.25 19.7293 34.25 21.7083V34.25C34.25 36.229 32.6457 37.8333 30.6667 37.8333H5.58333C3.60431 37.8333 2 36.229 2 34.25V21.7083C2 19.7293 3.60431 18.125 5.58333 18.125Z" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div class="relative min-w-0 flex-1">
                                        <input
                                            id="passInput"
                                            v-model="form.password"
                                            :type="showPassword ? 'text' : 'password'"
                                            autocomplete="current-password"
                                            placeholder=" "
                                            class="peer h-16 w-full border-0 bg-transparent px-1 pb-3 pt-6 text-base text-slate-900 outline-none"
                                            required
                                        >
                                        <label
                                            for="passInput"
                                            class="pointer-events-none absolute left-1 top-1/2 -translate-y-1/2 text-sm text-slate-500 transition-all duration-200 peer-placeholder-shown:top-1/2 peer-placeholder-shown:text-base peer-focus:top-4 peer-focus:text-sm peer-not-placeholder-shown:top-4 peer-not-placeholder-shown:text-sm"
                                        >
                                            Password
                                        </label>
                                    </div>
                                    <button
                                        type="button"
                                        class="mr-2 flex h-10 w-10 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                                        @click="showPassword = !showPassword"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-5 w-5">
                                            <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z" />
                                            <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z" />
                                            <path
                                                v-if="!showPassword"
                                                d="M2.2 1.4a.75.75 0 1 0-1.06 1.06l11.4 11.4a.75.75 0 0 0 1.06-1.06L2.2 1.4z"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
                            </div>

                            <div class="flex items-center justify-between gap-4 pt-1">
                                <label class="flex items-center gap-3 text-sm text-slate-600">
                                    <input
                                        v-model="form.remember"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-slate-900"
                                    >
                                    <span>Remember me</span>
                                </label>

                                <a href="/forgot-password" class="text-sm font-medium text-slate-500 transition hover:text-slate-900">
                                    Forgot password?
                                </a>
                            </div>

                            <button
                                type="submit"
                                class="mt-2 inline-flex w-full items-center justify-center rounded-xl bg-slate-800 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-70"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Signing in...' : 'Login !' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="relative hidden md:block">
                <img
                    src="/assets/images/MANDOSI-2-scaled.jpeg"
                    alt="Login Image"
                    class="h-screen w-full object-cover"
                >
            </div>
        </div>
    </div>
</template>
