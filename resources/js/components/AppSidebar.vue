<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BadgeDollarSign, BookOpen, ChartLine, ClipboardList, Coins, CopyCheck, FileCheckIcon, FilesIcon, FileText, FolderGit2, GitBranch, House, LayoutGrid, LucideGitBranchPlus, Network, ReceiptText, Target, User, WalletCards, Wrench } from 'lucide-vue-next';
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
import type { NavItem } from '@/types';
import Collapsible from './ui/collapsible/Collapsible.vue';
import SidebarGroup from './ui/sidebar/SidebarGroup.vue';
import SidebarGroupLabel from './ui/sidebar/SidebarGroupLabel.vue';
import CollapsibleTrigger from './ui/collapsible/CollapsibleTrigger.vue';
import { Tree } from 'reka-ui/namespaced';
import { TreeRoot } from 'reka-ui';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: House,
    },
    {
        title: 'Finance',
        href: '',
        icon: WalletCards,
        children: [
            {
                title: 'Billing',
                href: '',
                icon: BadgeDollarSign,
            },
            {
                title: 'Cost Realization',
                href: '',
                icon: FileCheckIcon,
            },
            {
                title: 'Profit and Loss',
                href: '',
                icon: ChartLine,
            }
        ]
    },
    {
        title: 'Marketing',
        href: '',
        icon: ClipboardList,
        children: [
            {
                title: 'Pipeline',
                href: '',
                icon: Network,
            },
            {
                title: 'Client',
                href: '',
                icon: User,
            },
            {
                title: 'Report',
                href: '',
                icon: FilesIcon,
            },
        ]
    },
    {
        title: 'Operational',
        href: '',
        icon: Wrench,
        children: [
            {
                title: 'Project Detail',
                href: '',
                icon: ReceiptText,
            },
            {
                title: 'RAB & RAP',
                href: '',
                icon: FileText,
            },
            {
                title: 'Progress Update',
                href: '',
                icon: CopyCheck,
            }
        ]
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
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

        <SidebarContent >            
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
