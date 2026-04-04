<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
<<<<<<< HEAD
import icons from 'lucide-vue-next';
=======
import { BadgeDollarSign, BookOpen, ChartLine, ClipboardList, Coins, CopyCheck, FileCheckIcon, FilesIcon, FileText, FolderGit2, GitBranch, House, LayoutGrid, LucideGitBranchPlus, Network, ReceiptText, Target, User, WalletCards, Wrench } from 'lucide-vue-next';
>>>>>>> 440a187901df57f3c1251b64832fa48023d2c47e
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
<<<<<<< HEAD
        label: 'Marketing',
        items: [          
            {
                title: 'Billing',
                href: '/revenue',
                icon: icons.BadgeDollarSign,
            },
            {
                title: 'Cost Realization',
                href: '/campaigns',
                icon: icons.FileCheckIcon,
            },
            {
                title: 'Profit and Loss',
                href: '/audience',
                icon: icons.ChartLine,
            },
        ],
    },
    {
        label: 'Marketing',
        items: [
            {
                title: 'pipelines',
                href: '/campaigns',
                icon: icons.Network,
            },
            {
                title: 'Client',
                href: '/audience',
                icon: icons.User,
            },
            {
                title: 'reports',
                href: '/audience',
                icon: icons.FilesIcon,
            },
        ],
    },
    {
        label: 'Operational',
        items: [
            {
                title: 'Project Details',
                href: '/campaigns',
                icon: icons.ReceiptText,
            },
            {
                title: 'RAB & RAP',
                href: '/audience',
                icon: icons.FileText,
            },
            {
                title: 'Progress Update',
                href: '/audience',
                icon: icons.CopyCheck,
            },
        ],
=======
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
>>>>>>> 440a187901df57f3c1251b64832fa48023d2c47e
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: icons.FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: icons.BookOpen,
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
