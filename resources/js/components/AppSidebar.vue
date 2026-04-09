<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import * as icons from 'lucide-vue-next';
import { computed } from 'vue';
import type { Component } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem, NavSection } from '@/types';
import type { SharedPageProps, SidebarItem, SidebarSection } from '@/types/sidebar';

const page = usePage<SharedPageProps>();

const permissions = computed(() => new Set(page.props.auth?.permissions ?? []));

const can = (permission: string) => permissions.value.has(permission);

const resolveIcon = (iconName: string): Component => {
    const icon = icons[iconName as keyof typeof icons];

    return (typeof icon === 'function' ? icon : icons.Circle) as unknown as Component;
};

const toNavItem = (item: SidebarItem): NavItem => ({
    title: item.title,
    href: item.href,
    icon: resolveIcon(item.icon) as NavItem['icon'],
});

const mainNavSections = computed<NavSection[]>(() =>
    (page.props.navigation?.sidebarSections ?? [])
        .map((section: SidebarSection) => ({
            label: section.label,
            items: section.items
                .filter((item) => can(item.permission))
                .map(toNavItem),
        }))
        .filter((section: NavSection) => section.items.length > 0),
);

const footerNavItems = computed<NavItem[]>(() =>
    (page.props.navigation?.footerItems ?? [])
        .filter((item: SidebarItem) => can(item.permission))
        .map(toNavItem),
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :sections="mainNavSections" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter v-if="footerNavItems.length > 0" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
