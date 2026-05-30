<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title?: string;
    description?: string;
    icon?: Component;
    bodyClass?: string;
}>();
</script>

<template>
    <section
        class="min-w-0 overflow-hidden rounded-xl border border-sidebar-border/70 bg-background p-3 shadow-sm sm:rounded-2xl sm:p-5"
    >
        <div
            v-if="title || description || icon"
            class="mb-4 flex min-w-0 items-start gap-2"
        >
            <component
                :is="icon"
                v-if="icon"
                class="mt-0.5 size-4 shrink-0 text-muted-foreground"
            />
            <div class="min-w-0">
                <h3
                    v-if="title"
                    class="text-base font-semibold break-words text-foreground sm:text-lg"
                >
                    {{ title }}
                </h3>
                <p
                    v-if="description"
                    class="text-xs break-words text-muted-foreground sm:text-sm"
                >
                    {{ description }}
                </p>
            </div>
        </div>

        <div class="min-w-0" :class="bodyClass">
            <slot />
        </div>
    </section>
</template>
