<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import * as icons from 'lucide-vue-next';
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

const mainNavSections: NavSection[] = [
    {
        label: null,
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: icons.Home,
            },
        ],
    },
    {
        label: 'Marketing',
        items: [          
            {
                title: 'Billing',
                href: '',
                icon: icons.BadgeDollarSign,
            },
            {
                title: 'Cost Realization',
                href: '',
                icon: icons.FileCheckIcon,
            },
            {
                title: 'Profit and Loss',
                href: '',
                icon: icons.ChartLine,
            },
        ],
    },
    {
        label: 'Marketing',
        items: [
            {
                title: 'Pipelines',
                href: '',
                icon: icons.Network,
            },
            {
                title: 'Client',
                href: '',
                icon: icons.User,
            },
            {
                title: 'Reports',
                href: '',
                icon: icons.FilesIcon,
            },
        ],
    },
    {
        label: 'Operational',
        items: [
            {
                title: 'Project Details',
                href: '',
                icon: icons.ReceiptText,
            },
            {
                title: 'RAB & RAP',
                href: '',
                icon: icons.FileText,
            },
            {
                title: 'Progress Update',
                href: '',
                icon: icons.CopyCheck,
            },
        ],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'testing',
        href: '/billing-test',
        icon: icons.TestTube,
    },
];
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
