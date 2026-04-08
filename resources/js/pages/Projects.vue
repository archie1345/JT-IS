<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import {
    ArrowDownAZ,
    ArrowUpAZ,
    Check,
    ExternalLink,
    Search,
    Settings2,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';

type PaymentStatus = 'pending' | 'paid' | 'overdue' | 'partial';
type ProjectStatus = 'planning' | 'ongoing' | 'completed';

type ProjectItem = {
    id: number;
    projectName: string;
    client: string;
    estPrice: number;
    deadline: string;
    paymentStatus: PaymentStatus;
    projectStatus: ProjectStatus;
};

const props = defineProps<{
    projects: ProjectItem[];
    activeClientId?: number | null;
}>();

type SortKey =
    | 'id'
    | 'projectName'
    | 'client'
    | 'estPrice'
    | 'deadline'
    | 'paymentStatus'
    | 'projectStatus';

const sortKey = ref<SortKey>('id');
const sortDirection = ref<'asc' | 'desc'>('desc');
const searchQuery = ref('');
const showColumnPicker = ref(false);
const activeHeaderFilter = ref<SortKey | null>(null);
const visibleColumns = ref<Record<SortKey, boolean>>({
    id: true,
    projectName: true,
    client: true,
    estPrice: true,
    deadline: true,
    paymentStatus: true,
    projectStatus: true,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Projects',
        href: '/projects',
    },
];

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const openProject = (item: ProjectItem) => {
    router.get('/client');
};

const formatPaymentStatus = (status: PaymentStatus) =>
    ({
        pending: 'Pending',
        partial: 'Partial',
        paid: 'Paid',
        overdue: 'Overdue',
    })[status] ?? status;

const formatProjectStatus = (status: ProjectStatus) =>
    ({
        planning: 'Planning',
        ongoing: 'Ongoing',
        completed: 'Completed',
    })[status] ?? status;

const columns: { key: SortKey; label: string }[] = [
    { key: 'id', label: 'Id' },
    { key: 'projectName', label: 'Project Name' },
    { key: 'client', label: 'Client' },
    { key: 'estPrice', label: 'Est. Value' },
    { key: 'deadline', label: 'Deadline' },
    { key: 'paymentStatus', label: 'Payment Status' },
    { key: 'projectStatus', label: 'Project Status' },
];

const visibleColumnList = computed(() =>
    columns.filter((column) => visibleColumns.value[column.key]),
);

const uniqueColumnValues = computed(() =>
    Object.fromEntries(
        columns.map((column) => [
            column.key,
            [...new Set(props.projects.map((project) => String(displayCellValue(project, column.key) ?? '-')))]
                .sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' })),
        ]),
    ) as Record<SortKey, string[]>,
);

const selectedColumnValues = ref<Record<SortKey, string[]>>(
    Object.fromEntries(columns.map((column) => [column.key, []])) as unknown as Record<SortKey, string[]>,
);
const draftSelectedColumnValues = ref<Record<SortKey, string[]>>(
    Object.fromEntries(columns.map((column) => [column.key, []])) as unknown as Record<SortKey, string[]>,
);

const filteredProjects = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    if (!query) {
        return props.projects;
    }

    return props.projects.filter((project) =>
        [
            project.id,
            project.projectName,
            project.client,
            project.estPrice,
            project.deadline,
            formatPaymentStatus(project.paymentStatus),
            formatProjectStatus(project.projectStatus),
        ].some((value) => String(value ?? '').toLowerCase().includes(query)),
    );
});

const columnFilteredProjects = computed(() =>
    filteredProjects.value.filter((project) =>
        columns.every((column) => {
            const displayValue = String(displayCellValue(project, column.key) ?? '-');
            const selectedValues = selectedColumnValues.value[column.key];

            const matchesSelectedValues =
                selectedValues.length === 0 || selectedValues.includes(displayValue);

            if (!matchesSelectedValues) {
                return false;
            }

            return true;
        }),
    ),
);

const sortedProjects = computed(() => {
    const direction = sortDirection.value === 'asc' ? 1 : -1;

    return [...columnFilteredProjects.value].sort((a, b) => {
        const aValue = a[sortKey.value];
        const bValue = b[sortKey.value];

        if (typeof aValue === 'number' && typeof bValue === 'number') {
            return (aValue - bValue) * direction;
        }

        return String(aValue ?? '').localeCompare(String(bValue ?? ''), undefined, {
            numeric: true,
            sensitivity: 'base',
        }) * direction;
    });
});

const toggleHeaderFilter = (key: SortKey) => {
    if (activeHeaderFilter.value === key) {
        activeHeaderFilter.value = null;
        return;
    }

    draftSelectedColumnValues.value[key] = [...selectedColumnValues.value[key]];
    activeHeaderFilter.value = key;
};

const applySort = (key: SortKey, direction: 'asc' | 'desc') => {
    sortKey.value = key;
    sortDirection.value = direction;
};

const clearColumnFilter = (key: SortKey) => {
    draftSelectedColumnValues.value[key] = [];
};

const toggleColumn = (key: SortKey) => {
    const activeCount = Object.values(visibleColumns.value).filter(Boolean).length;

    if (visibleColumns.value[key] && activeCount === 1) {
        return;
    }

    visibleColumns.value[key] = !visibleColumns.value[key];
};

const filteredColumnOptions = (key: SortKey) => {
    return uniqueColumnValues.value[key];
};

const isValueSelected = (key: SortKey, value: string) =>
    draftSelectedColumnValues.value[key].includes(value);

const toggleColumnValue = (key: SortKey, value: string) => {
    if (isValueSelected(key, value)) {
        draftSelectedColumnValues.value[key] = draftSelectedColumnValues.value[key].filter(
            (item) => item !== value,
        );
        return;
    }

    draftSelectedColumnValues.value[key] = [...draftSelectedColumnValues.value[key], value];
};

const selectAllColumnValues = (key: SortKey) => {
    draftSelectedColumnValues.value[key] = [];
};

const activeFilterCount = (key: SortKey) =>
    selectedColumnValues.value[key].length;

const cancelColumnFilter = (key: SortKey) => {
    draftSelectedColumnValues.value[key] = [...selectedColumnValues.value[key]];
    activeHeaderFilter.value = null;
};

const confirmColumnFilter = (key: SortKey) => {
    selectedColumnValues.value[key] = [...draftSelectedColumnValues.value[key]];
    activeHeaderFilter.value = null;
};

const closeAllPopups = () => {
    activeHeaderFilter.value = null;
    showColumnPicker.value = false;
};

const handlePointerDown = (event: PointerEvent) => {
    const target = event.target;

    if (!(target instanceof HTMLElement)) {
        return;
    }

    if (target.closest('[data-projects-filter-root]')) {
        return;
    }

    closeAllPopups();
};

const handleEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        closeAllPopups();
    }
};

onMounted(() => {
    window.addEventListener('pointerdown', handlePointerDown);
    window.addEventListener('keydown', handleEscape);
});

onBeforeUnmount(() => {
    window.removeEventListener('pointerdown', handlePointerDown);
    window.removeEventListener('keydown', handleEscape);
});

const displayCellValue = (item: ProjectItem, key: SortKey) => {
    if (key === 'estPrice') {
        return formatCurrency(item.estPrice);
    }

    if (key === 'deadline') {
        return item.deadline || '-';
    }

    if (key === 'paymentStatus') {
        return formatPaymentStatus(item.paymentStatus);
    }

    if (key === 'projectStatus') {
        return formatProjectStatus(item.projectStatus);
    }

    return item[key];
};

</script>

<template>
    <Head title="Projects" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-[calc(100vh-8rem)] flex-1 flex-col gap-4 overflow-hidden rounded-xl p-4">
            <section class="flex min-h-0 flex-1 flex-col rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm">
                <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-sidebar-border/70 bg-background shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">Projects</h2>
                            <p class="text-sm text-muted-foreground">
                                Project data below is loaded from the database.
                            </p>
                            <p v-if="props.activeClientId" class="text-xs text-muted-foreground">
                                Filtered by client ID: {{ props.activeClientId }}
                            </p>
                        </div>

                        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                            <div class="relative min-w-64">
                                <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="searchQuery"
                                    placeholder="Search like a spreadsheet..."
                                    class="pl-9"
                                />
                            </div>

                            <div class="relative" data-projects-filter-root>
                                <Button variant="outline" @click="showColumnPicker = !showColumnPicker">
                                    <Settings2 class="size-4" />
                                    Columns
                                </Button>

                                <div
                                    v-if="showColumnPicker"
                                    class="absolute top-full right-0 z-20 mt-2 w-56 rounded-xl border border-sidebar-border/70 bg-background p-3 shadow-lg"
                                >
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                                        Visible Columns
                                    </p>
                                    <label
                                        v-for="column in columns"
                                        :key="column.key"
                                        class="flex cursor-pointer items-center justify-between gap-3 rounded-md px-2 py-1.5 text-sm hover:bg-muted/50"
                                    >
                                        <span>{{ column.label }}</span>
                                        <input
                                            :checked="visibleColumns[column.key]"
                                            type="checkbox"
                                            class="h-4 w-4"
                                            @change="toggleColumn(column.key)"
                                        />
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-auto">
                        <table class="min-w-full text-sm">
                            <thead class="sticky top-0 z-10 bg-muted/95 text-left text-muted-foreground backdrop-blur">
                                <tr>
                                    <th
                                        v-for="column in visibleColumnList"
                                        :key="column.key"
                                        class="relative px-4 py-3"
                                        data-projects-filter-root
                                    >
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 font-medium text-muted-foreground transition hover:text-foreground"
                                            @click="toggleHeaderFilter(column.key)"
                                        >
                                            {{ column.label }}
                                            <ChevronDown class="size-4" />
                                            <span
                                                v-if="activeFilterCount(column.key) > 0"
                                                class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-semibold text-primary"
                                            >
                                                {{ activeFilterCount(column.key) }}
                                            </span>
                                        </button>

                                        <div
                                            v-if="activeHeaderFilter === column.key"
                                            class="absolute top-full left-4 z-20 mt-2 w-80 rounded-xl border border-sidebar-border/70 bg-background shadow-lg"
                                        >
                                            <div class="border-b border-sidebar-border/70 px-4 py-3">
                                                <button
                                                    type="button"
                                                    class="flex w-full items-center justify-between rounded-md px-1 py-2 text-left text-sm hover:bg-muted/50"
                                                    @click="applySort(column.key, 'asc')"
                                                >
                                                    <span>Sort A to Z</span>
                                                    <ArrowUpAZ class="size-4 text-muted-foreground" />
                                                </button>
                                                <button
                                                    type="button"
                                                    class="flex w-full items-center justify-between rounded-md px-1 py-2 text-left text-sm hover:bg-muted/50"
                                                    @click="applySort(column.key, 'desc')"
                                                >
                                                    <span>Sort Z to A</span>
                                                    <ArrowDownAZ class="size-4 text-muted-foreground" />
                                                </button>
                                            </div>

                                            <div class="px-4 py-3">
                                                <div class="mb-3 flex items-center justify-between text-xs">
                                                    <div class="space-x-1">
                                                        <button
                                                            type="button"
                                                            class="font-medium text-primary hover:underline"
                                                            @click="selectAllColumnValues(column.key)"
                                                        >
                                                            Select all
                                                        </button>
                                                        <span class="text-muted-foreground">-</span>
                                                        <button
                                                            type="button"
                                                            class="font-medium text-primary hover:underline"
                                                            @click="clearColumnFilter(column.key)"
                                                        >
                                                            Clear
                                                        </button>
                                                    </div>
                                                    <span class="text-muted-foreground">
                                                        Displaying {{ filteredColumnOptions(column.key).length }}
                                                    </span>
                                                </div>

                                                <div class="max-h-64 overflow-y-auto rounded-lg border border-sidebar-border/70 p-2">
                                                    <button
                                                        v-for="value in filteredColumnOptions(column.key)"
                                                        :key="value"
                                                        type="button"
                                                        class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-muted/50"
                                                        @click="toggleColumnValue(column.key, value)"
                                                    >
                                                        <span class="flex size-4 items-center justify-center">
                                                            <Check v-if="isValueSelected(column.key, value)" class="size-4" />
                                                        </span>
                                                        <span class="truncate">{{ value }}</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-end gap-3 border-t border-sidebar-border/70 px-4 py-3">
                                                <Button size="sm" variant="outline" @click="cancelColumnFilter(column.key)">
                                                    Cancel
                                                </Button>
                                                <Button size="sm" @click="confirmColumnFilter(column.key)">
                                                    OK
                                                </Button>
                                            </div>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in sortedProjects"
                                    :key="item.id"
                                    class="border-t border-sidebar-border/70 align-top"
                                >
                                    <td
                                        v-for="column in visibleColumnList"
                                        :key="column.key"
                                        class="px-4 py-3"
                                    >
                                        {{ displayCellValue(item, column.key) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end">
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                @click="openProject(item)"
                                            >
                                                <ExternalLink class="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="sortedProjects.length === 0">
                                    <td :colspan="visibleColumnList.length + 1" class="px-4 py-8 text-center text-muted-foreground">
                                        No matching project data found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
