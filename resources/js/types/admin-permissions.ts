export type RolePermissionOption = {
    name: string;
    label: string;
    description: string;
};

export type RolePermissionGroup = {
    key: string;
    label: string;
    description: string;
    permissions: RolePermissionOption[];
};

export type RolePermissionRow = {
    id: number;
    name: string;
    label: string;
    permissions: string[];
    userCount: number;
    isLocked: boolean;
};
