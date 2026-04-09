import type { Auth, SharedNavigation } from './auth';

export type SidebarItem = {
    title: string;
    href: string;
    icon: string;
    permission: string;
};

export type SidebarSection = {
    label: null | string;
    items: SidebarItem[];
};

export type SharedPageProps = {
    auth: Auth;
    navigation?: SharedNavigation;
};
