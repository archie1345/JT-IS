<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Building2, CheckCircle2, Mail, PencilLine, Plus, Save, UserRoundCog } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import ProjectDataTable, { type SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';
import type { AccountRole, AccountRoleOption, AdminStats, AdminUserRow, ClientChoice } from '@/types/user';

const props = defineProps<{
    users: AdminUserRow[];
    clients: ClientChoice[];
    stats: AdminStats;
    userTypes: AccountRoleOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Account Management',
        href: '/Admin_acc_mgmt',
    },
];

const defaultForm = {
    client_id: '',
    name: '',
    email: '',
    password: '',
    user_type: 'employee' as AccountRole,
};

const selectedUserId = ref<number | null>(null);
const isFormOpen = ref(false);

const form = useForm({ ...defaultForm });

const selectedUser = computed(
    () => props.users.find((user) => user.id === selectedUserId.value) ?? null,
);

const selectedClient = computed(
    () => props.clients.find((client) => String(client.id) === String(form.client_id)) ?? null,
);

const isEditing = computed(() => selectedUserId.value !== null);

const roleLabel = (role: AccountRole) =>
    props.userTypes.find((option) => option.value === role)?.label ?? role;

const roleBadgeClass = (role: AccountRole) =>
    ({
        admin: 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-500/25',
        employee: 'bg-blue-500/15 text-blue-600 ring-1 ring-blue-500/25',
        client: 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25',
        jte: 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/25',
    })[role];

const verificationBadgeClass = (verifiedAt: unknown) =>
    verifiedAt
        ? 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25'
        : 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-500/25';

const getUserRow = (row: Record<string, unknown>) => row as AdminUserRow;

const isClientRow = (row: Record<string, unknown>) => getUserRow(row).userType === 'client';

const userColumns = [
    { key: 'id', label: 'Id' },
    { key: 'name', label: 'Name', accessor: (row: Record<string, unknown>) => (row as AdminUserRow).name },
    { key: 'email', label: 'Email', accessor: (row: Record<string, unknown>) => (row as AdminUserRow).email },
    {
        key: 'userTypeLabel',
        label: 'Role',
        accessor: (row: Record<string, unknown>) => (row as AdminUserRow).userTypeLabel,
    },
    {
        key: 'clientName',
        label: 'Client',
        accessor: (row: Record<string, unknown>) => (row as AdminUserRow).clientName ?? '-',
    },
    {
        key: 'verifiedAt',
        label: 'Verified',
        accessor: (row: Record<string, unknown>) => (row as AdminUserRow).verifiedAt ?? '-',
    },
    {
        key: 'createdAt',
        label: 'Created',
        accessor: (row: Record<string, unknown>) => (row as AdminUserRow).createdAt ?? '-',
    },
] satisfies SpreadsheetColumn[];

const loadUser = (row: Record<string, unknown>) => {
    const user = getUserRow(row);

    selectedUserId.value = user.id;
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.user_type = user.userType;
    form.client_id = user.clientId ? String(user.clientId) : '';
};

const resetToCreate = () => {
    selectedUserId.value = null;
    form.clearErrors();
    form.reset();
    form.client_id = '';
    form.user_type = 'employee';
};

const cancelEdit = () => {
    isFormOpen.value = false;
};

const submit = () => {
    if (isEditing.value && selectedUserId.value) {
        form.patch(`/Admin_acc_mgmt/${selectedUserId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                form.password = '';
                isFormOpen.value = false;
            },
        });
        return;
    }

    form.post('/Admin_acc_mgmt', {
        preserveScroll: true,
        onSuccess: () => {
            resetToCreate();
            isFormOpen.value = false;
        },
    });
};

const openCreateModal = () => {
    resetToCreate();
    isFormOpen.value = true;
};

const openEditModal = (row: Record<string, unknown>) => {
    loadUser(row);
    isFormOpen.value = true;
};

watch(
    () => form.user_type,
    (role) => {
        if (role !== 'client') {
            form.client_id = '';
        }
    },
);
</script>

<template>
    <Head title="Account Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-4 rounded-xl p-4">
            <section class="flex min-h-0 flex-1 flex-col gap-4 rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-semibold tracking-tight text-foreground">
                            Account Management
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Create and edit accounts for clients, internal employees, and admins from one place.
                        </p>
                    </div>

                    <Badge class="bg-blue-500/15 text-blue-600 ring-1 ring-blue-500/25">
                        {{ props.stats.total }} Accounts
                    </Badge>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Total</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">{{ props.stats.total }}</p>
                    </div>
                    <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Admin</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">{{ props.stats.admin }}</p>
                    </div>
                    <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Employee</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">{{ props.stats.employee }}</p>
                    </div>
                    <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">JTE / Internal</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">{{ props.stats.jte }}</p>
                    </div>
                    <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 shadow-sm">
                        <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Client</p>
                        <p class="mt-2 text-2xl font-semibold text-foreground">{{ props.stats.client }}</p>
                    </div>
                </div>

                <div class="flex min-h-0 flex-1 flex-col gap-4">
                    <div class="flex min-h-0 flex-col">
                        <ProjectDataTable
                            :rows="props.users"
                            :columns="userColumns"
                            title="Users"
                            description="Click a row to edit the account or use the new account button to create one."
                            note="Internal role options include Employee and JTE / Internal for legacy accounts."
                            row-key-field="id"
                            :stretch-to-viewport="false"
                            show-create-button
                            create-label="New Account"
                            empty-text="No accounts found."
                            @row-click="openEditModal"
                            @create="openCreateModal"
                        >
                            <template #cell-userTypeLabel="{ value, row }">
                                <Badge :class="roleBadgeClass(getUserRow(row).userType)" class="whitespace-nowrap">
                                    {{ value }}
                                </Badge>
                            </template>

                            <template #cell-clientName="{ value, row }">
                                <span
                                    class="inline-flex items-center gap-2"
                                    :class="isClientRow(row) ? 'text-foreground' : 'text-muted-foreground'"
                                >
                                    <Building2 class="size-4 text-muted-foreground" />
                                    {{ value || '-' }}
                                </span>
                            </template>

                            <template #cell-verifiedAt="{ value }">
                                <Badge :class="verificationBadgeClass(value)">
                                    {{ value || 'Pending' }}
                                </Badge>
                            </template>

                            <template #cell-createdAt="{ value }">
                                <span class="text-sm text-muted-foreground">{{ value || '-' }}</span>
                            </template>

                            <template #actions="{ row }">
                                <Button variant="ghost" size="icon-sm" @click.stop="openEditModal(row)">
                                    <PencilLine class="size-4" />
                                </Button>
                            </template>
                        </ProjectDataTable>
                    </div>
                </div>

                <Dialog v-model:open="isFormOpen">
                    <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                        <DialogHeader>
                            <DialogTitle class="flex items-center gap-2 text-xl">
                                <UserRoundCog class="size-5 text-muted-foreground" />
                                {{ isEditing ? 'Edit Account' : 'Create Account' }}
                            </DialogTitle>
                            <DialogDescription>
                                Create admin, employee, client, or legacy JTE accounts from one popup.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="space-y-4">
                            <div class="rounded-xl bg-muted/30 p-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Preview</p>
                                <p class="mt-1 text-lg font-semibold text-foreground">
                                    {{ form.name || 'Account name' }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ form.email || 'account@email.com' }}
                                </p>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <Badge :class="roleBadgeClass(form.user_type)">
                                        {{ roleLabel(form.user_type) }}
                                    </Badge>
                                    <Badge v-if="form.user_type === 'client'" variant="outline">
                                        {{ selectedClient?.name ?? 'Client linked here' }}
                                    </Badge>
                                </div>
                            </div>

                            <label class="space-y-2">
                                <span class="text-sm font-medium text-foreground">Name</span>
                                <Input v-model="form.name" placeholder="Full name" />
                                <InputError :message="form.errors.name" />
                            </label>

                            <label class="space-y-2">
                                <span class="text-sm font-medium text-foreground">Email</span>
                                <div class="relative">
                                    <Mail class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input v-model="form.email" type="email" placeholder="name@example.com" class="pl-9" />
                                </div>
                                <InputError :message="form.errors.email" />
                            </label>

                            <label class="space-y-2">
                                <span class="text-sm font-medium text-foreground">
                                    Password
                                    <span class="text-xs text-muted-foreground">
                                        {{ isEditing ? '(leave blank to keep current password)' : '(required)' }}
                                    </span>
                                </span>
                                <Input
                                    v-model="form.password"
                                    type="password"
                                    :placeholder="isEditing ? 'Leave blank to keep current password' : 'Temporary password'"
                                />
                                <InputError :message="form.errors.password" />
                            </label>

                            <label class="space-y-2">
                                <span class="text-sm font-medium text-foreground">Role</span>
                                <Select v-model="form.user_type">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Choose role" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in props.userTypes"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p class="text-xs text-muted-foreground">
                                    Client accounts need a linked client record. Employee and admin accounts do not.
                                </p>
                                <InputError :message="form.errors.user_type" />
                            </label>

                            <label v-if="form.user_type === 'client'" class="space-y-2">
                                <span class="text-sm font-medium text-foreground">Linked Client</span>
                                <Select v-model="form.client_id">
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Choose a client" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="client in props.clients"
                                            :key="client.id"
                                            :value="String(client.id)"
                                        >
                                            {{ client.name ?? `Client #${client.id}` }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.client_id" />
                            </label>

                            <div v-else class="rounded-xl border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground">
                                No client link is needed for this role.
                            </div>

                            <div class="rounded-xl bg-muted/30 p-4 text-sm text-muted-foreground">
                                <div class="flex items-center gap-2 text-foreground">
                                    <CheckCircle2 class="size-4 text-emerald-500" />
                                    {{ selectedUser ? `Editing ${selectedUser.name}` : 'Ready to create a new account' }}
                                </div>
                                <p class="mt-1">
                                    Selected client: {{ selectedClient?.name ?? '-' }}
                                </p>
                                <p>
                                    This account will be saved into the users table with soft deletes enabled.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <Button variant="outline" type="button" class="w-full" @click="cancelEdit">
                                Cancel
                            </Button>
                            <Button type="button" class="w-full" :disabled="form.processing" @click="submit">
                                <Plus v-if="!isEditing" class="mr-2 size-4" />
                                <Save v-else class="mr-2 size-4" />
                                {{ isEditing ? 'Save Changes' : 'Create Account' }}
                            </Button>
                        </div>
                    </DialogContent>
                </Dialog>
            </section>
        </div>
    </AppLayout>
</template>
