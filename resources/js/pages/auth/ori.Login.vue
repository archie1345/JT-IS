<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import AuthBase from '@/layouts/AuthLayout.vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase
        title="Login ke Akun"
        description="Masukkan Email dan password untuk melanjutkan."
    >
        <Head title="Login" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Form
            action="/login"
            method="post"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="space-y-5"
        >
            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:bg-white focus:outline-none"
                >
                <p v-if="errors.email" class="mt-2 text-sm text-red-600">{{ errors.email }}</p>
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between gap-4">
                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                    <a
                        v-if="canResetPassword"
                        href="/forgot-password"
                        class="text-sm font-medium text-slate-500 transition hover:text-slate-900"
                    >
                        Lupa password?
                    </a>
                </div>
                <input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Password"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-900 focus:bg-white focus:outline-none"
                >
                <p v-if="errors.password" class="mt-2 text-sm text-red-600">{{ errors.password }}</p>
            </div>

            <label class="flex items-center gap-3 text-sm text-slate-600">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    class="h-4 w-4 rounded border-slate-300 text-slate-900"
                >
                <span>Ingat saya</span>
            </label>

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-70"
                :disabled="processing"
            >
                {{ processing ? 'Memproses...' : 'Login' }}
            </button>
        </Form>
    </AuthBase>
</template>
