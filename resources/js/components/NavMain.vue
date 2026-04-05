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
            class="px-2 py-0"
        >
            <SidebarGroupLabel v-if="section.label">{{ section.label }}</SidebarGroupLabel>

            <SidebarMenu>
                <SidebarMenuItem
                    v-for="item in section.items"
                    :key="item.title"
                >
                    <SidebarMenuButton
                        as-child
                        class="transition-all duration-200 ease-out hover:translate-x-1 group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:!justify-center group-data-[collapsible=icon]:hover:translate-x-0"
                        :is-active="item.href ? isCurrentUrl(item.href) : false"
                    >
                        <Link :href="item.href" class="flex items-center gap-2">
                            <component :is="item.icon" class="shrink-0" />
                            <span class="group-data-[collapsible=icon]:hidden">{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    </div>
</template>