<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';
import Collapsible from './ui/collapsible/Collapsible.vue';
import CollapsibleTrigger from './ui/collapsible/CollapsibleTrigger.vue';
import CollapsibleContent from './ui/collapsible/CollapsibleContent.vue';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentOrParentUrl, isCurrentUrl } = useCurrentUrl();

const hasActiveChild = (item: NavItem): boolean =>
    item.children?.some((child) => {
        const isChildActive = child.href
            ? isCurrentOrParentUrl(child.href)
            : false;

        return isChildActive || hasActiveChild(child);
    }) ?? false;
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>

        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <Collapsible
                    v-if="item.children"
                    v-slot="{ open }"
                    :default-open="hasActiveChild(item)"
                >
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton
                                class="group/collapsible transition-colors duration-200"
                            >
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                                <ChevronRight
                                    class="ml-auto size-4 text-muted-foreground transition-transform duration-300 ease-out"
                                    :class="open ? 'rotate-90' : 'rotate-0'"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>

                        <CollapsibleContent force-mount>
                            <div
                                class="grid transition-[grid-template-rows,opacity,margin] duration-300 ease-out"
                                :class="open ? 'mt-1 grid-rows-[1fr] opacity-100' : 'mt-0 grid-rows-[0fr] opacity-0'"
                            >
                                <div class="min-h-0 overflow-hidden">
                                    <div class="ml-6 flex flex-col gap-1 border-l border-sidebar-border/60 pl-3">
                                        <SidebarMenuItem
                                            v-for="sub in item.children"
                                            :key="sub.title"
                                        >
                                            <SidebarMenuButton
                                                as-child
                                                class="transition-all duration-200 ease-out hover:translate-x-1"
                                                :is-active="sub.href ? isCurrentUrl(sub.href) : false"
                                            >
                                                <Link :href="sub.href">
                                                    <component :is="sub.icon" v-if="sub.icon" />
                                                    <span>{{ sub.title }}</span>
                                                </Link>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    </div>
                                </div>
                            </div>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>

                <SidebarMenuItem v-else>
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
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
