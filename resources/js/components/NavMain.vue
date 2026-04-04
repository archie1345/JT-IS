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
                        class="transition-all duration-200 ease-out hover:translate-x-1"
                        :is-active="item.href ? isCurrentUrl(item.href) : false"
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroup>
    </div>
</template>
