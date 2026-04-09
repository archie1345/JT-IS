<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    backLabel: string;
    title: string;
    description: string;
    badgeText: string;
    badgeClass?: string;
    titlePrefix?: string;
    metricLabel?: string;
    metricValue?: string | number;
    metricDescription?: string;
    progressLabel?: string;
    progressValue?: string | number;
    progressBarValue?: number;
    progressToneClass?: string;
    summaryGridClass?: string;
    backIcon?: Component;
}>();

const emit = defineEmits<{
    back: [];
}>();
</script>

<template>
    <section class="rounded-2xl border border-sidebar-border/70 bg-background p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <slot name="back">
                    <button
                        type="button"
                        class="mb-3 inline-flex items-center text-sm text-muted-foreground transition hover:text-foreground"
                        @click="emit('back')"
                    >
                        <component :is="backIcon" v-if="backIcon" class="mr-2 size-4" />
                        {{ backLabel }}
                    </button>
                </slot>

                <h1 class="text-3xl font-semibold tracking-tight text-foreground">{{ title }}</h1>
                <p class="mt-2 text-sm text-muted-foreground">{{ description }}</p>
            </div>

            <slot name="badge">
                <div class="rounded-xl px-3 py-1.5 text-sm font-medium" :class="badgeClass">
                    {{ badgeText }}
                </div>
            </slot>
        </div>

        <div class="mt-5 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p v-if="titlePrefix" class="text-xs uppercase tracking-[0.2em] text-muted-foreground">
                    {{ titlePrefix }}
                </p>
                <slot name="title-input" />
                <slot name="title-meta" />
            </div>

            <div v-if="metricLabel || $slots.metric" class="rounded-2xl border border-sidebar-border/70 bg-muted/30 px-4 py-3">
                <slot name="metric">
                    <p class="text-xs text-muted-foreground">{{ metricLabel }}</p>
                    <p class="text-3xl font-semibold text-foreground">{{ metricValue }}</p>
                    <p v-if="metricDescription" class="text-xs text-muted-foreground">{{ metricDescription }}</p>
                </slot>
            </div>
        </div>

        <div v-if="progressLabel || $slots.progress" class="mt-5">
            <slot name="progress">
                <div class="mb-2 flex items-center justify-between text-xs text-muted-foreground">
                    <span>{{ progressLabel }}</span>
                    <span>{{ progressValue }}</span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full transition-all"
                        :class="progressToneClass"
                        :style="{ width: `${progressBarValue ?? 0}%` }"
                    />
                </div>
            </slot>
        </div>

        <div v-if="$slots.summary" class="mt-5" :class="summaryGridClass ?? 'grid gap-4 md:grid-cols-2'">
            <slot name="summary" />
        </div>
    </section>
</template>
