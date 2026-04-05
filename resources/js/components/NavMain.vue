<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavSection } from '@/types';

defineProps<{
    sections: NavSection[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <div>
        <SidebarGroup
            v-for="(section, index) in sections"
            :key="`${section.label ?? '-'}-${index}`"
            class="px-2 py-0 rounded-lg"
        >
            <SidebarGroupLabel v-if="section.label">{{ section.label }}</SidebarGroupLabel>

            <SidebarMenu>
                <SidebarMenuItem
                    v-for="item in section.items"
                    :key="item.title"
                >
                    <SidebarMenuButton
                        as-child
                        class="my-1 transition-[padding,width] duration-200 delay-[0ms,100ms] ease-linear group-data-[collapsible=icon]:mx-auto"
                        :is-active="item.href ? isCurrentUrl(item.href) : false"
                    >
                        <Link :href="item.href" class="flex min-w-0 items-center gap-2 transition-[gap] duration-200 ease-linear group-data-[collapsible=icon]:gap-0">
                            <component :is="item.icon" class="shrink-0" />
                            <span class="block max-w-[12rem] overflow-hidden whitespace-nowrap opacity-100 transition-[max-width,opacity] duration-150 ease-linear group-data-[collapsible=icon]:opacity-0">{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    </div>
</template>