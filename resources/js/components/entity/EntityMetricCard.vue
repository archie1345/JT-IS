<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    description?: string;
}>();
</script>

<template>
    <div
        class="min-w-0 overflow-hidden rounded-xl border border-sidebar-border/70 bg-background p-3 shadow-sm sm:p-4"
    >
        <p
            class="text-[11px] break-words text-muted-foreground uppercase sm:text-xs"
        >
            {{ label }}
        </p>
        <p
            class="mt-2 text-xl font-semibold break-words text-foreground sm:text-2xl"
        >
            {{ value }}
        </p>
        <p
            v-if="description"
            class="mt-1 text-xs break-words text-muted-foreground"
        >
            {{ description }}
        </p>
    </div>
</template>
