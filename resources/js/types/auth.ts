export type DashboardLayout = {
    order?: string[];
    visible?: string[];
};

export type User = {
    id: number;
    name: string;
    email: string;
    user_type?: string;
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

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
