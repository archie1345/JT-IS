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
    <section
        class="min-w-0 overflow-hidden rounded-xl border border-sidebar-border/70 bg-background p-3 shadow-sm sm:rounded-2xl sm:p-5"
    >
        <div class="flex min-w-0 flex-wrap items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
                <slot name="back">
                    <button
                        type="button"
                        class="mb-3 inline-flex items-center text-sm text-muted-foreground transition hover:text-foreground"
                        @click="emit('back')"
                    >
                        <component
                            :is="backIcon"
                            v-if="backIcon"
                            class="mr-2 size-4"
                        />
                        {{ backLabel }}
                    </button>
                </slot>

                <h1
                    class="text-2xl font-semibold tracking-tight break-words text-foreground sm:text-3xl"
                >
                    {{ title }}
                </h1>
                <p class="mt-2 text-sm break-words text-muted-foreground">
                    {{ description }}
                </p>
            </div>

            <slot name="badge">
                <div
                    class="rounded-xl px-3 py-1.5 text-sm font-medium"
                    :class="badgeClass"
                >
                    {{ badgeText }}
                </div>
            </slot>
        </div>

        <div class="mt-5 flex min-w-0 flex-col items-start gap-4">
            <div class="min-w-0 flex-1">
                <p
                    v-if="titlePrefix"
                    class="text-xs tracking-[0.2em] text-muted-foreground uppercase"
                >
                    {{ titlePrefix }}
                </p>
                <slot name="title-input" />
                <slot name="title-meta" />
            </div>

            <div
                v-if="metricLabel || $slots.metric"
                class="w-full min-w-0 rounded-xl border border-sidebar-border/70 bg-muted/30 px-3 py-3 sm:w-auto sm:rounded-2xl sm:px-4"
            >
                <slot name="metric">
                    <p class="text-xs text-muted-foreground">
                        {{ metricLabel }}
                    </p>
                    <p
                        class="text-2xl font-semibold break-words text-foreground sm:text-3xl"
                    >
                        {{ metricValue }}
                    </p>
                    <p
                        v-if="metricDescription"
                        class="text-xs text-muted-foreground"
                    >
                        {{ metricDescription }}
                    </p>
                </slot>
            </div>
        </div>

        <div v-if="progressLabel || $slots.progress" class="mt-5">
            <slot name="progress">
                <div
                    class="mb-2 flex items-center justify-between text-xs text-muted-foreground"
                >
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

        <div
            v-if="$slots.summary"
            class="mt-5 min-w-0"
            :class="summaryGridClass ?? 'grid gap-4 md:grid-cols-2'"
        >
            <slot name="summary" />
        </div>
    </section>
</template>
