<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, LockKeyhole, Save } from 'lucide-vue-next';
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
    if (role.isLocked) return;

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
    if (role.isLocked) return;

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
        class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-background shadow-sm"
    >
        <div
            class="flex flex-col gap-4 border-b border-sidebar-border/70 p-4 lg:flex-row lg:items-start lg:justify-between"
        >
            <div>
                <p class="text-xs font-medium text-muted-foreground uppercase">
                    Access Settings
                </p>
                <h2 class="mt-1 text-xl font-semibold text-foreground">
                    Role Permission Table
                </h2>
                <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
                    Set which pages, menus, and actions each role can use.
                    Related permissions are added automatically when saved.
                </p>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center text-xs sm:min-w-72">
                <div class="rounded-lg border border-sidebar-border/70 p-2">
                    <span class="block text-lg font-semibold text-foreground">
                        {{ props.roles.length }}
                    </span>
                    <span class="text-muted-foreground">Roles</span>
                </div>
                <div class="rounded-lg border border-sidebar-border/70 p-2">
                    <span class="block text-lg font-semibold text-foreground">
                        {{ props.permissionGroups.length }}
                    </span>
                    <span class="text-muted-foreground">Groups</span>
                </div>
                <div class="rounded-lg border border-sidebar-border/70 p-2">
                    <span class="block text-lg font-semibold text-foreground">
                        {{ dirtyRoles.length }}
                    </span>
                    <span class="text-muted-foreground">Unsaved</span>
                </div>
            </div>
        </div>

        <div
            class="grid gap-3 border-b border-sidebar-border/70 bg-muted/20 p-3 lg:grid-cols-[minmax(0,1fr)_auto]"
        >
            <Input
                v-model="searchTerm"
                placeholder="Search roles, permissions, descriptions"
            />
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-md border px-3 py-2 text-sm transition"
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
                    class="rounded-md border px-3 py-2 text-sm transition"
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

        <div v-else>
            <div
                class="flex flex-col gap-3 border-b border-sidebar-border/70 p-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <p class="text-sm text-muted-foreground">
                    Toggle cells, then save the changed role columns.
                </p>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="role in visibleRoles"
                        :key="`save-${role.id}`"
                        type="button"
                        size="sm"
                        :variant="isDirty(role) ? 'default' : 'outline'"
                        :disabled="
                            role.isLocked || saving[role.name] || !isDirty(role)
                        "
                        @click="saveRole(role)"
                    >
                        <Save class="mr-2 size-4" />
                        {{ saving[role.name] ? 'Saving' : role.label }}
                    </Button>
                </div>
            </div>

            <div class="overflow-auto">
                <table
                    class="min-w-[76rem] border-separate border-spacing-0 text-sm"
                >
                    <thead>
                        <tr>
                            <th
                                class="sticky top-0 left-0 z-30 w-[24rem] border-r border-b border-sidebar-border/70 bg-background px-4 py-3 text-left font-medium text-foreground"
                            >
                                Permission
                            </th>
                            <th
                                v-for="role in visibleRoles"
                                :key="role.id"
                                class="sticky top-0 z-20 min-w-40 border-r border-b border-sidebar-border/70 bg-background px-3 py-3 text-center"
                            >
                                <div class="flex flex-col items-center gap-1">
                                    <span class="font-medium text-foreground">
                                        {{ role.label }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ role.userCount }} users
                                    </span>
                                    <span
                                        v-if="role.isLocked"
                                        class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                                    >
                                        <LockKeyhole class="size-3" />
                                        locked
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
                                    class="border-b border-sidebar-border/70 bg-muted/30 px-4 py-3"
                                >
                                    <div
                                        class="flex flex-wrap items-end justify-between gap-2"
                                    >
                                        <div>
                                            <p
                                                class="font-medium text-foreground"
                                            >
                                                {{ group.label }}
                                            </p>
                                            <p
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ group.description }}
                                            </p>
                                        </div>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                group.permissions.length
                                            }}
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
                                    class="sticky left-0 z-10 border-r border-b border-sidebar-border/60 bg-background px-4 py-3 group-hover:bg-muted/20"
                                >
                                    <p class="font-medium text-foreground">
                                        {{ permission.label }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ permission.description }}
                                    </p>
                                    <p
                                        class="mt-1 text-[11px] text-muted-foreground/70"
                                    >
                                        {{ permission.name }}
                                    </p>
                                </td>
                                <td
                                    v-for="role in visibleRoles"
                                    :key="`${role.id}-${permission.name}`"
                                    class="border-r border-b border-sidebar-border/60 px-3 py-3 text-center group-hover:bg-muted/20"
                                >
                                    <button
                                        type="button"
                                        class="mx-auto inline-flex h-8 w-14 items-center justify-center rounded-full border transition"
                                        :class="
                                            isChecked(
                                                role.name,
                                                permission.name,
                                            )
                                                ? 'border-emerald-500/40 bg-emerald-500/15 text-emerald-700'
                                                : 'border-sidebar-border/70 bg-muted/20 text-muted-foreground hover:bg-muted/40'
                                        "
                                        :disabled="role.isLocked"
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
    </section>
</template>
