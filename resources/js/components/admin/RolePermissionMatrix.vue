<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, Save } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type {
    RolePermissionGroup,
    RolePermissionRow,
} from '@/types/admin-permissions';

const props = defineProps<{
    roles: RolePermissionRow[];
    permissionGroups: RolePermissionGroup[];
}>();

const selections = reactive<Record<string, string[]>>({});
const saving = reactive<Record<string, boolean>>({});
const searchTerm = ref('');
const selectedGroup = ref<string>('all');

const syncSelections = () => {
    Object.keys(selections).forEach((key) => {
        delete selections[key];
    });

    props.roles.forEach((role) => {
        selections[role.name] = [...role.permissions].sort();
        saving[role.name] = false;
    });
};

watch(() => props.roles, syncSelections, { immediate: true, deep: true });

const normalizedSearch = computed(() => searchTerm.value.trim().toLowerCase());

const visibleGroups = computed(() =>
    props.permissionGroups.filter((group) => {
        if (
            selectedGroup.value !== 'all' &&
            group.key !== selectedGroup.value
        ) {
            return false;
        }

        if (normalizedSearch.value === '') {
            return true;
        }

        return group.permissions.some((permission) =>
            [
                permission.label,
                permission.description,
                permission.name,
                group.label,
            ]
                .join(' ')
                .toLowerCase()
                .includes(normalizedSearch.value),
        );
    }),
);

const visibleRoles = computed(() =>
    props.roles.filter((role) => {
        if (normalizedSearch.value === '') {
            return true;
        }

        return [role.label, role.name]
            .join(' ')
            .toLowerCase()
            .includes(normalizedSearch.value);
    }),
);

const isChecked = (roleName: string, permissionName: string) =>
    selections[roleName]?.includes(permissionName) ?? false;

const togglePermission = (
    roleName: string,
    permissionName: string,
    checked: boolean,
) => {
    const current = new Set(selections[roleName] ?? []);

    if (checked) {
        current.add(permissionName);
    } else {
        current.delete(permissionName);
    }

    selections[roleName] = Array.from(current).sort();
};

const toggleFromCell = (role: RolePermissionRow, permissionName: string) => {
    togglePermission(
        role.name,
        permissionName,
        !isChecked(role.name, permissionName),
    );
};

const isDirty = (role: RolePermissionRow) => {
    const next = [...(selections[role.name] ?? [])].sort().join('|');
    const current = [...role.permissions].sort().join('|');

    return next !== current;
};

const dirtyRoles = computed(() =>
    visibleRoles.value.filter((role) => isDirty(role)),
);

const saveRole = (role: RolePermissionRow) => {
    saving[role.name] = true;

    router.patch(
        `/Admin_acc_mgmt/roles/${role.id}`,
        {
            permissions: selections[role.name] ?? [],
        },
        {
            preserveScroll: true,
            onFinish: () => {
                saving[role.name] = false;
            },
        },
    );
};
</script>

<template>
    <section
        class="min-w-0 overflow-hidden rounded-xl border border-sidebar-border/70 bg-background shadow-sm"
    >
        <div
            class="flex min-w-0 flex-col gap-3 border-b border-sidebar-border/70 p-3 sm:p-4 lg:flex-row lg:items-center lg:justify-between"
        >
            <div class="min-w-0">
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Access Settings
                </p>
                <h2
                    class="mt-1 text-base font-semibold break-words text-foreground sm:text-lg"
                >
                    Role Permission Table
                </h2>
                <p
                    class="mt-1 max-w-3xl text-xs break-words text-muted-foreground"
                >
                    Set which pages, menus, and actions each role can use.
                    Related permissions are added automatically when saved.
                </p>
            </div>

            <div
                class="grid w-full grid-cols-3 gap-2 text-center text-[11px] sm:w-auto sm:min-w-56 sm:text-xs"
            >
                <div class="rounded-md border border-sidebar-border/70 p-2">
                    <span
                        class="block text-sm font-semibold text-foreground sm:text-base"
                    >
                        {{ props.roles.length }}
                    </span>
                    <span class="text-muted-foreground">Roles</span>
                </div>
                <div class="rounded-md border border-sidebar-border/70 p-2">
                    <span
                        class="block text-sm font-semibold text-foreground sm:text-base"
                    >
                        {{ props.permissionGroups.length }}
                    </span>
                    <span class="text-muted-foreground">Groups</span>
                </div>
                <div class="rounded-md border border-sidebar-border/70 p-2">
                    <span
                        class="block text-sm font-semibold text-foreground sm:text-base"
                    >
                        {{ dirtyRoles.length }}
                    </span>
                    <span class="text-muted-foreground">Unsaved</span>
                </div>
            </div>
        </div>

        <div
            class="grid min-w-0 gap-3 border-b border-sidebar-border/70 bg-muted/20 p-3 lg:grid-cols-[minmax(0,1fr)_auto]"
        >
            <Input
                v-model="searchTerm"
                placeholder="Search roles, permissions, descriptions"
                class="min-w-0 text-sm"
            />
            <div class="flex min-w-0 flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-md border px-2.5 py-2 text-xs transition sm:px-3 sm:text-sm"
                    :class="
                        selectedGroup === 'all'
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-sidebar-border/70 bg-background text-muted-foreground hover:text-foreground'
                    "
                    @click="selectedGroup = 'all'"
                >
                    All
                </button>
                <button
                    v-for="group in props.permissionGroups"
                    :key="group.key"
                    type="button"
                    class="max-w-full rounded-md border px-2.5 py-2 text-xs break-words transition sm:px-3 sm:text-sm"
                    :class="
                        selectedGroup === group.key
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-sidebar-border/70 bg-background text-muted-foreground hover:text-foreground'
                    "
                    @click="selectedGroup = group.key"
                >
                    {{ group.label }}
                </button>
            </div>
        </div>

        <div
            v-if="visibleRoles.length === 0 || visibleGroups.length === 0"
            class="m-4 rounded-lg border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground"
        >
            No matching roles or permissions.
        </div>

        <div v-else class="min-w-0 overflow-hidden">
            <div
                class="flex min-w-0 flex-col gap-3 border-b border-sidebar-border/70 p-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <p class="text-xs text-muted-foreground">
                    Toggle cells, then save the changed role columns.
                </p>
                <div class="flex min-w-0 flex-wrap gap-2">
                    <Button
                        v-for="role in visibleRoles"
                        :key="`save-${role.id}`"
                        type="button"
                        size="sm"
                        :variant="isDirty(role) ? 'default' : 'outline'"
                        :disabled="saving[role.name] || !isDirty(role)"
                        @click="saveRole(role)"
                    >
                        <Save class="mr-2 size-4" />
                        {{ saving[role.name] ? 'Saving' : role.label }}
                    </Button>
                </div>
            </div>

            <div class="relative min-w-0 overflow-x-hidden">
                <div
                    class="table-scrollbar max-w-full overflow-auto border-t border-sidebar-border/70 pb-2"
                >
                    <table
                        class="w-max min-w-full border-separate border-spacing-0 text-xs sm:text-sm"
                    >
                        <thead>
                            <tr>
                                <th
                                    class="sticky top-0 left-0 z-30 min-w-[14rem] border-r border-b border-sidebar-border/70 bg-background px-3 py-2 text-left font-medium text-foreground sm:min-w-[20rem] sm:px-4"
                                >
                                    Permission
                                </th>
                                <th
                                    v-for="role in visibleRoles"
                                    :key="role.id"
                                    class="sticky top-0 z-20 min-w-24 border-r border-b border-sidebar-border/70 bg-background px-2 py-2 text-center sm:min-w-32 sm:px-3"
                                >
                                    <div
                                        class="flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="font-medium text-foreground"
                                        >
                                            {{ role.label }}
                                        </span>
                                        <span
                                            class="text-[11px] text-muted-foreground sm:text-xs"
                                        >
                                            {{ role.userCount }} users
                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template
                                v-for="group in visibleGroups"
                                :key="group.key"
                            >
                                <tr>
                                    <td
                                        :colspan="visibleRoles.length + 1"
                                        class="border-b border-sidebar-border/70 bg-muted/30 px-3 py-2 sm:px-4"
                                    >
                                        <div
                                            class="flex flex-wrap items-end justify-between gap-2"
                                        >
                                            <div>
                                                <p
                                                    class="text-xs font-medium break-words text-foreground sm:text-sm"
                                                >
                                                    {{ group.label }}
                                                </p>
                                                <p
                                                    class="text-[11px] break-words text-muted-foreground sm:text-xs"
                                                >
                                                    {{ group.description }}
                                                </p>
                                            </div>
                                            <span
                                                class="text-[11px] text-muted-foreground sm:text-xs"
                                            >
                                                {{ group.permissions.length }}
                                                permissions
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-for="permission in group.permissions"
                                    :key="permission.name"
                                    class="group"
                                >
                                    <td
                                        class="sticky left-0 z-10 min-w-[14rem] border-r border-b border-sidebar-border/40 bg-background px-3 py-2 group-hover:bg-muted/20 sm:min-w-[20rem] sm:px-4"
                                    >
                                        <p
                                            class="text-xs font-medium break-words text-foreground sm:text-sm"
                                        >
                                            {{ permission.label }}
                                        </p>
                                        <p
                                            class="mt-0.5 text-[11px] text-muted-foreground/70"
                                        >
                                            {{ permission.name }}
                                        </p>
                                    </td>
                                    <td
                                        v-for="role in visibleRoles"
                                        :key="`${role.id}-${permission.name}`"
                                        class="border-r border-b border-sidebar-border/40 px-2 py-2 text-center group-hover:bg-muted/20 sm:px-3"
                                    >
                                        <button
                                            type="button"
                                            class="mx-auto inline-flex h-8 w-12 items-center justify-center rounded-full border transition sm:w-14"
                                            :class="
                                                isChecked(
                                                    role.name,
                                                    permission.name,
                                                )
                                                    ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-700'
                                                    : 'border-sidebar-border/70 bg-muted/20 text-muted-foreground hover:bg-muted/40'
                                            "
                                            :aria-pressed="
                                                isChecked(
                                                    role.name,
                                                    permission.name,
                                                )
                                            "
                                            :aria-label="`${role.label} ${permission.label}`"
                                            @click="
                                                toggleFromCell(
                                                    role,
                                                    permission.name,
                                                )
                                            "
                                        >
                                            <Check
                                                v-if="
                                                    isChecked(
                                                        role.name,
                                                        permission.name,
                                                    )
                                                "
                                                class="size-4"
                                            />
                                            <span
                                                v-else
                                                class="h-0.5 w-4 rounded bg-current"
                                            />
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</template>
