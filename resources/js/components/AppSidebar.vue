<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import * as icons from 'lucide-vue-next';
import { computed } from 'vue';
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
import type { Auth } from '@/types';

type SidebarNavItem = NavItem & {
    allowedRoles?: string[];
};

type SidebarNavSection = {
    label: null | string;
    items: SidebarNavItem[];
};

type PageAuth = {
    roles?: string[] | null;
    user?: Auth['user'] | null;
};

const page = usePage<{ auth: PageAuth }>();

const isAdmin = computed(
    () =>
        page.props.auth?.user?.user_type === 'admin' ||
        page.props.auth?.roles?.includes('admin') === true,
);

const userRoles = computed(() => {
    const roles = page.props.auth?.roles ?? [];

    return new Set(roles);
});

const canSee = (allowedRoles?: string[]) => {
    if (isAdmin.value) {
        return true;
    }

    if (!allowedRoles || allowedRoles.length === 0) {
        return true;
    }

    return allowedRoles.some((role) => userRoles.value.has(role));
};

const filterSection = (
    section: SidebarNavSection,
): SidebarNavSection | null => {
    const items = section.items.filter((item) => canSee(item.allowedRoles));

    if (items.length === 0) {
        return null;
    }

    return {
        ...section,
        items,
    };
};

const rawMainNavSections: SidebarNavSection[] = [
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
        label: 'Finance',
        items: [
            {
                title: 'Billing',
                href: '',
                icon: icons.BadgeDollarSign,
                allowedRoles: ['admin', 'employee'],
            },
            {
                title: 'Cost Realization',
                href: '',
                icon: icons.FileCheckIcon,
                allowedRoles: ['admin', 'employee'],
            },
            {
                title: 'Profit and Loss',
                href: '',
                icon: icons.ChartLine,
                allowedRoles: ['admin', 'employee'],
            },
        ],
    },
    {
        label: 'Marketing',
        items: [
            {
                title: 'Projects',
                href: '/projects',
                icon: icons.Network,
                allowedRoles: ['admin', 'employee'],
            },
            {
                title: 'Client',
                href: '/client',
                icon: icons.Users,
                allowedRoles: ['admin', 'employee'],
            },
            {
                title: 'Reports',
                href: '',
                icon: icons.FilesIcon,
                allowedRoles: ['admin', 'employee'],
            },
        ],
    },
    {
        label: 'Operational',
        items: [
            {
                title: 'RAB',
                href: '/rabs',
                icon: icons.FileText,
                allowedRoles: ['admin', 'employee'],
            },
            {
                title: 'RAP',
                href: '/raps',
                icon: icons.FileText,
                allowedRoles: ['admin', 'employee'],
            },
            {
                title: 'Progress Update',
                href: '',
                icon: icons.CopyCheck,
                allowedRoles: ['admin', 'employee'],
            },
        ],
    },
];

const mainNavSections = computed<SidebarNavSection[]>(() =>
    rawMainNavSections
        .map(filterSection)
        .filter((section): section is SidebarNavSection => section !== null),
);

const rawFooterNavItems: SidebarNavItem[] = [
    {
        title: 'testing',
        href: '/billing-test',
        icon: icons.TestTube,
        allowedRoles: ['admin', 'employee'],
    },
    {
        title: 'Account Management',
        href: '/Admin_acc_mgmt',
        icon: icons.Settings,
        allowedRoles: ['admin'],
    },
];

const footerNavItems = computed<SidebarNavItem[]>(() =>
    rawFooterNavItems.filter((item) => canSee(item.allowedRoles)),
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
            <NavFooter
                v-if="footerNavItems.length > 0"
                :items="footerNavItems"
            />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
