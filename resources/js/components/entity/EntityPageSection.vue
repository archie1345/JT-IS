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
    <section class="rounded-2xl border border-sidebar-border/70 bg-background p-5 shadow-sm">
        <div v-if="title || description || icon" class="mb-4 flex items-center gap-2">
            <component :is="icon" v-if="icon" class="size-4 text-muted-foreground" />
            <div>
                <h3 v-if="title" class="text-lg font-semibold text-foreground">{{ title }}</h3>
                <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
            </div>
        </div>

        <div :class="bodyClass">
            <slot />
        </div>
    </section>
</template>
