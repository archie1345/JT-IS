export type DashboardLayout = {
    order?: string[];
    visible?: string[];
};

export type User = {
    id: number;
    name: string;
    email: string;
    user_type?: string;
    employee_role?: string | null;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    dashboard_layout?: DashboardLayout | string[] | null;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    roles: string[];
    permissions: string[];
};

export type SharedNavigationItem = {
    title: string;
    href: string;
    icon: string;
    permission: string;
};

export type SharedNavigationSection = {
    label: null | string;
    items: SharedNavigationItem[];
};

export type SharedNavigation = {
    sidebarSections: SharedNavigationSection[];
    footerItems: SharedNavigationItem[];
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
