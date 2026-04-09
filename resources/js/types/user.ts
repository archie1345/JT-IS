export type AccountRole = 'admin' | 'employee' | 'client';

export type AccountRoleOption = {
    value: AccountRole;
    label: string;
};

export type EmployeeRoleOption = {
    value: string;
    label: string;
};

export type AdminUserRow = {
    id: number;
    clientId: number | null;
    name: string;
    email: string;
    userType: AccountRole;
    userTypeLabel: string;
    employeeRole: string | null;
    employeeRoleLabel: string | null;
    clientName: string | null;
    verifiedAt: string | null;
    createdAt: string | null;
};

export type AdminStats = {
    total: number;
    admin: number;
    employee: number;
    client: number;
};

export type ClientChoice = {
    id: number;
    name: string | null;
};
