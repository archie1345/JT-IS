<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AdminAccountManagementRbac from '@/components/admin/AdminAccountManagementRbac.vue';
import RolePermissionMatrix from '@/components/admin/RolePermissionMatrix.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type {
    RolePermissionGroup,
    RolePermissionRow,
} from '@/types/admin-permissions';
import type {
    AccountRoleOption,
    AdminStats,
    AdminUserRow,
    ClientChoice,
    EmployeeRoleOption,
} from '@/types/user';

const props = defineProps<{
    users: AdminUserRow[];
    clients: ClientChoice[];
    stats: AdminStats;
    userTypes: AccountRoleOption[];
    employeeRoleSuggestions: EmployeeRoleOption[];
    roles: RolePermissionRow[];
    permissionGroups: RolePermissionGroup[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Account Management',
        href: '/Admin_acc_mgmt',
    },
];
</script>

<template>
    <Head title="Account Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 rounded-xl p-4">
            <AdminAccountManagementRbac
                :users="props.users"
                :clients="props.clients"
                :stats="props.stats"
                :user-types="props.userTypes"
                :employee-role-suggestions="props.employeeRoleSuggestions"
            />

            <RolePermissionMatrix
                :roles="props.roles"
                :permission-groups="props.permissionGroups"
            />
        </div>
    </AppLayout>
</template>
