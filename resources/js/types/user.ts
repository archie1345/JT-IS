export type AccountRole = 'admin' | 'employee' | 'client' | 'jte';

export type AccountRoleOption = {
    value: AccountRole;
    label: string;
};

export type AdminUserRow = {
    id: number;
    clientId: number | null;
    name: string;
    email: string;
    userType: AccountRole;
    userTypeLabel: string;
    clientName: string | null;
    verifiedAt: string | null;
    createdAt: string | null;
};

export type AdminStats = {
    total: number;
    admin: number;
    employee: number;
    jte: number;
    client: number;
};

export type ClientChoice = {
    id: number;
    name: string | null;
};
