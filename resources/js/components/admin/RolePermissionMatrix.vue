<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, ChevronDown, ChevronRight, Save } from 'lucide-vue-next';
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
const collapsedGroups = ref<Record<string, boolean>>({});
const isSavingChanges = ref(false);

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

watch(
    () => props.permissionGroups,
    (groups) => {
        const groupKeys = new Set(groups.map((group) => group.key));

        Object.keys(collapsedGroups.value).forEach((key) => {
            if (!groupKeys.has(key)) {
                delete collapsedGroups.value[key];
            }
        });
    },
    { immediate: true, deep: true },
);

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
    props.roles.filter((role) => role.name !== 'admin'),
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

const isGroupCollapsed = (groupKey: string) =>
    collapsedGroups.value[groupKey] ?? false;

const toggleGroup = (groupKey: string) => {
    collapsedGroups.value[groupKey] = !isGroupCollapsed(groupKey);
};

type RoleSavePayload = {
    id: number;
    name: string;
    permissions: string[];
};

const saveQueuedRoles = (roles: RoleSavePayload[], index = 0) => {
    const role = roles[index];

    if (!role) {
        isSavingChanges.value = false;
        return;
    }

    saving[role.name] = true;

    router.patch(
        `/Admin_acc_mgmt/roles/${role.id}`,
        {
            permissions: role.permissions,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                saving[role.name] = false;
                saveQueuedRoles(roles, index + 1);
            },
        },
    );
};

const saveDirtyRoles = () => {
    const roles = dirtyRoles.value.map((role) => ({
        id: role.id,
        name: role.name,
        permissions: [...(selections[role.name] ?? [])],
    }));

    if (roles.length === 0 || isSavingChanges.value) {
        return;
    }

    isSavingChanges.value = true;
    saveQueuedRoles(roles);
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
                    Pengaturan Akses
                </p>
                <h2
                    class="mt-1 text-base font-semibold break-words text-foreground sm:text-lg"
                >
                    Tabel Permission Role
                </h2>
                <p
                    class="mt-1 max-w-3xl text-xs break-words text-muted-foreground"
                >
                    Atur halaman, menu, dan aksi yang bisa digunakan setiap
                    role. Permission terkait ditambahkan otomatis saat
                    disimpan.
                </p>
            </div>
        </div>

        <div
            class="grid min-w-0 gap-3 border-b border-sidebar-border/70 bg-muted/20 p-3 lg:grid-cols-[minmax(0,1fr)_auto]"
        >
            <Input
                v-model="searchTerm"
                placeholder="Cari role, permission, deskripsi"
                class="min-w-0 text-sm"
            />
        </div>

        <div
            v-if="visibleRoles.length === 0 || visibleGroups.length === 0"
            class="m-4 rounded-lg border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground"
        >
            Role atau permission tidak ditemukan.
        </div>

        <div v-else class="min-w-0 overflow-hidden">
            <div
                class="flex min-w-0 flex-col gap-3 border-b border-sidebar-border/70 p-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <p class="text-xs text-muted-foreground">
                    Aktifkan sel yang dibutuhkan, lalu simpan perubahan role.
                </p>
                <div class="flex min-w-0 flex-wrap gap-2">
                    <Button
                        type="button"
                        size="sm"
                        :variant="dirtyRoles.length > 0 ? 'default' : 'outline'"
                        :disabled="isSavingChanges || dirtyRoles.length === 0"
                        @click="saveDirtyRoles"
                    >
                        <Save class="mr-2 size-4" />
                        {{
                            isSavingChanges
                                ? 'Menyimpan'
                                : `Simpan Perubahan (${dirtyRoles.length})`
                        }}
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
                                            {{ role.userCount }} user
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
                                        class="border-b border-sidebar-border/70 bg-muted/35 p-0"
                                    >
                                        <button
                                            type="button"
                                            class="flex w-full min-w-0 items-center gap-3 px-3 py-3 text-left transition hover:bg-muted/60 sm:px-4"
                                            :aria-expanded="
                                                !isGroupCollapsed(group.key)
                                            "
                                            @click="toggleGroup(group.key)"
                                        >
                                            <ChevronRight
                                                v-if="
                                                    isGroupCollapsed(group.key)
                                                "
                                                class="size-4 shrink-0 text-muted-foreground"
                                            />
                                            <ChevronDown
                                                v-else
                                                class="size-4 shrink-0 text-muted-foreground"
                                            />
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate text-xs font-semibold text-foreground uppercase sm:text-sm"
                                                    :title="group.label"
                                                >
                                                    {{ group.label }}
                                                </p>
                                                <p
                                                    class="truncate text-[11px] text-muted-foreground sm:text-xs"
                                                    :title="group.description"
                                                >
                                                    {{ group.description }}
                                                </p>
                                            </div>
                                        </button>
                                    </td>
                                </tr>
                                <template v-if="!isGroupCollapsed(group.key)">
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
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</template>
