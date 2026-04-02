<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-slate-100 px-6 py-10 text-slate-950 sm:px-8 lg:px-12">
        <div class="mx-auto flex min-h-[calc(100vh-5rem)] w-full max-w-md items-center">
            <div class="w-full rounded-[2rem] bg-white p-8 shadow-[0_30px_80px_-40px_rgba(15,23,42,0.35)] sm:p-10">
                <Link href="/" class="mb-8 inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900">
                    Back to home
                </Link>

                <div class="space-y-2">
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm leading-6 text-slate-500">
                        {{ description }}
                    </p>
                </div>

                <div class="mt-8">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
