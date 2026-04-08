<script setup lang="ts">
import { computed } from 'vue';
import { SidebarInset } from '@/components/ui/sidebar';

type Props = {
    variant?: 'header' | 'sidebar';
    class?: string;
};

const props = defineProps<Props>();
const className = computed(() => props.class);
const backgroundLayerClass =
    "absolute inset-0 bg-[url('/assets/svg/JTE_Logo_only.svg')] bg-center bg-fixed bg-contain bg-no-repeat opacity-15";
</script>

<template>
    <SidebarInset
        v-if="props.variant === 'sidebar'"
        :class="['relative overflow-hidden', className]"
    >
        <div :class="backgroundLayerClass" />
        <div class="relative z-10">
            <slot />
        </div>
    </SidebarInset>
    <main
        v-else
        class="relative mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4"
        :class="className"
    >
        <div :class="backgroundLayerClass" />
        <div class="relative z-10 flex h-full flex-1 flex-col gap-4">
            <slot />
        </div>
    </main>
</template>
