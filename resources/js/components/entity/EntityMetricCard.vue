<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    description?: string;
}>();
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 shadow-sm">
        <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">{{ label }}</p>
        <p class="mt-2 text-2xl font-semibold text-foreground">{{ value }}</p>
        <p v-if="description" class="mt-1 text-xs text-muted-foreground">{{ description }}</p>
    </div>
</template>
