<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
        if (selectedGroup.value !== 'all' && group.key !== selectedGroup.value) {
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

const handleCheckboxChange = (
    roleName: string,
    permissionName: string,
    event: Event,
) => {
    const target = event.target;

    if (!(target instanceof HTMLInputElement)) {
        return;
    }

    togglePermission(roleName, permissionName, target.checked);
};

const isDirty = (role: RolePermissionRow) => {
    const next = [...(selections[role.name] ?? [])].sort().join('|');
    const current = [...role.permissions].sort().join('|');

    return next !== current;
};

const saveRole = (role: RolePermissionRow) => {
    if (role.isLocked) {
        return;
    }

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
    <section class="rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                    Role Permission Matrix
                </h2>
                <p class="text-sm text-muted-foreground">
                    Choose which sidebar items, pages, and CRUD actions each role can use.
                </p>
            </div>
            <Badge class="bg-amber-500/15 text-amber-700 ring-1 ring-amber-500/25">
                {{ props.roles.length }} Roles
            </Badge>
        </div>

        <div class="mt-5 grid gap-3 rounded-xl border border-sidebar-border/60 bg-muted/20 p-3 md:grid-cols-[minmax(0,1fr)_13rem]">
            <Input
                v-model="searchTerm"
                placeholder="Filter roles or permissions"
            />
            <Select v-model="selectedGroup">
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="All groups" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All groups</SelectItem>
                    <SelectItem
                        v-for="group in props.permissionGroups"
                        :key="group.key"
                        :value="group.key"
                    >
                        {{ group.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div
            v-if="visibleRoles.length === 0 || visibleGroups.length === 0"
            class="mt-4 rounded-xl border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground"
        >
            No matching roles or permission groups found for this filter.
        </div>

        <div v-else class="mt-5 grid gap-4 xl:grid-cols-2">
            <article
                v-for="role in visibleRoles"
                :key="role.id"
                class="rounded-2xl border border-sidebar-border/70 bg-background p-4 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-semibold text-foreground">
                                {{ role.label }}
                            </h3>
                            <Badge variant="outline">
                                {{ role.userCount }} users
                            </Badge>
                            <Badge
                                v-if="role.isLocked"
                                class="bg-slate-500/15 text-slate-700 ring-1 ring-slate-500/25"
                            >
                                Locked
                            </Badge>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            <span v-if="role.isLocked">
                                The admin role stays full-access to avoid lockout.
                            </span>
                            <span v-else>
                                Toggle permissions below, then save this role.
                            </span>
                        </p>
                    </div>

                    <Button
                        type="button"
                        size="sm"
                        :disabled="role.isLocked || saving[role.name] || !isDirty(role)"
                        @click="saveRole(role)"
                    >
                        {{ saving[role.name] ? 'Saving...' : 'Save Role' }}
                    </Button>
                </div>

                <div class="mt-4 space-y-4">
                    <section
                        v-for="group in visibleGroups"
                        :key="`${role.id}-${group.key}`"
                        class="rounded-xl border border-sidebar-border/60 bg-muted/20 p-4"
                    >
                        <div>
                            <h4 class="font-medium text-foreground">{{ group.label }}</h4>
                            <p class="text-xs text-muted-foreground">{{ group.description }}</p>
                        </div>

                        <div class="mt-3 space-y-3">
                            <label
                                v-for="permission in group.permissions"
                                :key="permission.name"
                                class="flex items-start gap-3 rounded-lg border border-transparent px-1 py-1.5 transition hover:border-sidebar-border/70"
                            >
                                <input
                                    type="checkbox"
                                    class="mt-1 size-4 rounded border-sidebar-border text-primary"
                                    :checked="isChecked(role.name, permission.name)"
                                    :disabled="role.isLocked"
                                    @change="handleCheckboxChange(role.name, permission.name, $event)"
                                />
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-foreground">
                                        {{ permission.label }}
                                    </span>
                                    <span class="block text-xs text-muted-foreground">
                                        {{ permission.description }}
                                    </span>
                                    <span class="block text-[11px] text-muted-foreground/80">
                                        {{ permission.name }}
                                    </span>
                                </span>
                            </label>
                        </div>
                    </section>
                </div>
            </article>
        </div>
    </section>
</template>
