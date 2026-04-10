<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch, type CSSProperties } from 'vue';
import {
    ArrowDownAZ,
    ArrowUpAZ,
    Check,
    ChevronDown,
    ExternalLink,
    Plus,
    Search,
    Settings2,
} from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

export type SpreadsheetColumn = {
    key: string;
    label: string;
    accessor?: (row: any) => unknown;
    visible?: boolean;
    sortable?: boolean;
    filterable?: boolean;
    widthClass?: string;
};

type SortDirection = 'asc' | 'desc';
type TableRow = Record<string, unknown>;

const props = withDefaults(defineProps<{
    rows: TableRow[];
    columns: SpreadsheetColumn[];
    title: string;
    description?: string;
    note?: string;
    rowKeyField?: string;
    searchPlaceholder?: string;
    showCreateButton?: boolean;
    createLabel?: string;
    emptyText?: string;
    stretchToViewport?: boolean;
}>(), {
    rowKeyField: 'id',
    searchPlaceholder: 'Search like a spreadsheet...',
    showCreateButton: false,
    createLabel: 'New Item',
    emptyText: 'No matching data found.',
    stretchToViewport: true,
});

const emit = defineEmits<{
    rowClick: [row: TableRow];
    create: [];
}>();

const sortKey = ref<string>('');
const sortDirection = ref<SortDirection>('asc');
const searchQuery = ref('');
const showColumnPicker = ref(false);
const activeHeaderFilter = ref<string | null>(null);
const headerFilterStyle = ref<CSSProperties>({});
const headerFilterSearch = ref<Record<string, string>>({});
const visibleColumns = ref<Record<string, boolean>>({});
const selectedColumnValues = ref<Record<string, string[]>>({});
const draftSelectedColumnValues = ref<Record<string, string[]>>({});

const resolvedColumns = computed(() => props.columns.filter((column) => visibleColumns.value[column.key] !== false));
const filterableColumns = computed(() => props.columns.filter((column) => column.filterable !== false));

const columnValue = (row: TableRow, column: SpreadsheetColumn) => {
    const value = column.accessor ? column.accessor(row) : row[column.key];
    return value ?? '-';
};

const displayValue = (row: TableRow, column: SpreadsheetColumn) =>
    String(columnValue(row, column));

const uniqueColumnValues = computed(() =>
    Object.fromEntries(
        filterableColumns.value.map((column) => [
            column.key,
            [...new Set(props.rows.map((row) => displayValue(row, column)))]
                .sort((a, b) => a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' })),
        ]),
    ) as Record<string, string[]>,
);

const createDefaultSelections = () =>
    Object.fromEntries(
        props.columns.map((column) => [column.key, [...(uniqueColumnValues.value[column.key] ?? [])]]),
    ) as Record<string, string[]>;

const resetState = () => {
    const columnVisibility = Object.fromEntries(
        props.columns.map((column) => [column.key, column.visible ?? true]),
    ) as Record<string, boolean>;

    visibleColumns.value = columnVisibility;
    headerFilterSearch.value = Object.fromEntries(props.columns.map((column) => [column.key, ''])) as Record<string, string>;
    selectedColumnValues.value = createDefaultSelections();
    draftSelectedColumnValues.value = createDefaultSelections();

    if (sortKey.value === '' && props.columns.length > 0) {
        sortKey.value = props.columns[0].key;
    }
};

watch(
    () => [props.rows, props.columns],
    () => {
        resetState();
    },
    { immediate: true, deep: true },
);

const filteredRows = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    if (!query) {
        return props.rows;
    }

    return props.rows.filter((row) =>
        props.columns.some((column) => displayValue(row, column).toLowerCase().includes(query)),
    );
});

const columnFilteredRows = computed(() =>
    filteredRows.value.filter((row) =>
        filterableColumns.value.every((column) => {
            const value = displayValue(row, column);
            return selectedColumnValues.value[column.key]?.includes(value) ?? true;
        }),
    ),
);

const sortedRows = computed(() => {
    if (!sortKey.value) {
        return columnFilteredRows.value;
    }

    const direction = sortDirection.value === 'asc' ? 1 : -1;
    const column = props.columns.find((item) => item.key === sortKey.value);

    return [...columnFilteredRows.value].sort((a, b) => {
        const aValue = column ? columnValue(a, column) : a[sortKey.value];
        const bValue = column ? columnValue(b, column) : b[sortKey.value];

        if (typeof aValue === 'number' && typeof bValue === 'number') {
            return (aValue - bValue) * direction;
        }

        return String(aValue ?? '').localeCompare(String(bValue ?? ''), undefined, {
            numeric: true,
            sensitivity: 'base',
        }) * direction;
    });
});

const activeHeaderColumn = computed(
    () => props.columns.find((column) => column.key === activeHeaderFilter.value) ?? null,
);

const openRow = (row: TableRow) => emit('rowClick', row);
const createItem = () => emit('create');

const openHeaderFilter = (event: MouseEvent, key: string) => {
    const column = props.columns.find((item) => item.key === key);

    if (column?.sortable === false && column?.filterable === false) {
        return;
    }

    if (activeHeaderFilter.value === key) {
        activeHeaderFilter.value = null;
        return;
    }

    draftSelectedColumnValues.value[key] = [...(selectedColumnValues.value[key] ?? [])];
    headerFilterSearch.value[key] = '';
    activeHeaderFilter.value = key;

    const target = event.currentTarget;

    if (!(target instanceof HTMLElement)) {
        headerFilterStyle.value = {};
        return;
    }

    const rect = target.getBoundingClientRect();
    const viewportPadding = 16;
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const popupWidth = Math.min(320, viewportWidth - viewportPadding * 2);

    const left = Math.min(
        Math.max(viewportPadding, rect.left),
        Math.max(viewportPadding, viewportWidth - popupWidth - viewportPadding),
    );

    const top = rect.bottom + 8;
    const maxTop = viewportHeight - viewportPadding - 24;

    headerFilterStyle.value = {
        position: 'fixed',
        top: `${Math.min(top, maxTop)}px`,
        left: `${left}px`,
        width: `${popupWidth}px`,
        zIndex: '60',
    };
};

const applySort = (key: string, direction: SortDirection) => {
    const column = props.columns.find((item) => item.key === key);

    if (column?.sortable === false) {
        return;
    }

    sortKey.value = key;
    sortDirection.value = direction;
};

const clearColumnFilter = (key: string) => {
    draftSelectedColumnValues.value[key] = [];
};

const filteredColumnOptions = (key: string) => {
    const column = props.columns.find((item) => item.key === key);

    if (column?.filterable === false) {
        return [];
    }

    const query = (headerFilterSearch.value[key] ?? '').trim().toLowerCase();
    const values = uniqueColumnValues.value[key] ?? [];

    if (!query) {
        return values;
    }

    return values.filter((value) => value.toLowerCase().includes(query));
};

const isValueSelected = (key: string, value: string) =>
    draftSelectedColumnValues.value[key]?.includes(value) ?? false;

const toggleColumnValue = (key: string, value: string) => {
    if (isValueSelected(key, value)) {
        draftSelectedColumnValues.value[key] = (draftSelectedColumnValues.value[key] ?? []).filter(
            (item) => item !== value,
        );
        return;
    }

    draftSelectedColumnValues.value[key] = [...(draftSelectedColumnValues.value[key] ?? []), value];
};

const selectAllColumnValues = (key: string) => {
    draftSelectedColumnValues.value[key] = [...(uniqueColumnValues.value[key] ?? [])];
};

const activeFilterCount = (key: string) => selectedColumnValues.value[key]?.length ?? 0;

const cancelColumnFilter = (key: string) => {
    draftSelectedColumnValues.value[key] = [...(selectedColumnValues.value[key] ?? [])];
    headerFilterSearch.value[key] = '';
    activeHeaderFilter.value = null;
};

const confirmColumnFilter = (key: string) => {
    selectedColumnValues.value[key] = [...(draftSelectedColumnValues.value[key] ?? [])];
    headerFilterSearch.value[key] = '';
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

    if (target.closest('[data-spreadsheet-table-root]')) {
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
</script>

<template>
    <div
        class="flex flex-1 flex-col gap-3 overflow-hidden rounded-xl p-3 sm:gap-4 sm:p-4"
        :class="props.stretchToViewport ? 'h-[calc(100vh-8rem)]' : 'h-full min-h-0'"
    >
        <section class="flex min-h-0 flex-1 flex-col rounded-2xl border border-sidebar-border/70 bg-background/80 p-3 shadow-sm sm:p-5">
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-sidebar-border/70 bg-background shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 px-3 py-3 sm:px-5 sm:py-4">
                    <div>
                        <h2 class="text-base font-semibold text-foreground sm:text-lg">{{ props.title }}</h2>
                        <p v-if="props.description" class="text-xs text-muted-foreground sm:text-sm">
                            {{ props.description }}
                        </p>
                        <p v-if="props.note" class="text-[11px] text-muted-foreground sm:text-xs">
                            {{ props.note }}
                        </p>
                    </div>

                    <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                        <div class="relative w-full sm:min-w-64">
                            <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                :placeholder="props.searchPlaceholder"
                                class="h-10 pl-9 text-sm"
                            />
                        </div>

                        <div v-if="props.showCreateButton" class="relative w-full sm:w-auto" data-spreadsheet-table-root>
                            <Button variant="default" class="w-full text-sm sm:w-auto" @click="createItem">
                                <Plus class="size-4" />
                                {{ props.createLabel }}
                            </Button>
                        </div>

                        <div class="relative w-full sm:w-auto" data-spreadsheet-table-root>
                            <Button
                                variant="outline"
                                class="w-full text-sm sm:w-auto"
                                @click="showColumnPicker = !showColumnPicker"
                            >
                                <Settings2 class="size-4" />
                                Columns
                            </Button>

                            <div
                                v-if="showColumnPicker"
                                class="absolute top-full right-0 z-20 mt-2 w-[min(18rem,calc(100vw-2rem))] rounded-xl border border-sidebar-border/70 bg-background p-3 shadow-lg sm:w-56"
                            >
                                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                                    Visible Columns
                                </p>
                                <label
                                    v-for="column in props.columns"
                                    :key="column.key"
                                    class="flex cursor-pointer items-center justify-between gap-3 rounded-md px-2 py-1.5 text-sm hover:bg-muted/50"
                                >
                                    <span>{{ column.label }}</span>
                                    <input
                                        v-model="visibleColumns[column.key]"
                                        type="checkbox"
                                        class="h-4 w-4"
                                    />
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative min-h-0 flex-1 overflow-auto">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs sm:text-sm">
                        <thead class="sticky top-0 z-10 bg-muted/95 text-left text-muted-foreground backdrop-blur">
                            <tr>
                                <th
                                    v-for="column in resolvedColumns"
                                    :key="column.key"
                                    class="relative px-3 py-2.5 sm:px-4 sm:py-3"
                                    data-spreadsheet-table-root
                                    :class="column.widthClass"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 text-left font-medium text-muted-foreground transition hover:text-foreground sm:gap-2"
                                        @click="openHeaderFilter($event, column.key)"
                                    >
                                        {{ column.label }}
                                        <ChevronDown class="size-4 shrink-0 text-muted-foreground/90 sm:size-5" />
                                        <span
                                            v-if="activeFilterCount(column.key) > 0"
                                            class="rounded bg-primary/10 px-1.5 py-0.5 text-[10px] font-semibold text-primary"
                                        >
                                            {{ activeFilterCount(column.key) }}
                                        </span>
                                    </button>
                                </th>
                                <th class="px-4 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in sortedRows"
                                :key="String(row[props.rowKeyField] ?? JSON.stringify(row))"
                                class="border-t border-sidebar-border/70 align-top"
                            >
                                <td
                                    v-for="column in resolvedColumns"
                                    :key="column.key"
                                    class="px-3 py-3 sm:px-4 sm:py-3"
                                >
                                    <slot :name="`cell-${column.key}`" :row="row" :column="column" :value="columnValue(row, column)">
                                        {{ columnValue(row, column) }}
                                    </slot>
                                </td>
                                <td class="px-3 py-3 sm:px-4 sm:py-3">
                                    <div class="flex justify-end">
                                        <slot name="actions" :row="row">
                                            <Button variant="ghost" size="icon-sm" @click="openRow(row)">
                                                <ExternalLink class="size-4" />
                                            </Button>
                                        </slot>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="sortedRows.length === 0">
                                <td
                                    :colspan="resolvedColumns.length + 1"
                                    class="px-4 py-8 text-center text-xs text-muted-foreground sm:text-sm"
                                >
                                    {{ props.emptyText }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </section>

        <Teleport v-if="activeHeaderColumn" to="body">
            <div
                data-spreadsheet-table-root
                :style="headerFilterStyle"
                class="rounded-xl border border-sidebar-border/70 bg-background shadow-lg"
            >
                <div class="border-b border-sidebar-border/70 px-4 py-3">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-md px-1 py-2 text-left text-sm hover:bg-muted/50"
                        @click="applySort(activeHeaderColumn.key, 'asc')"
                    >
                        <span>Sort A to Z</span>
                        <ArrowUpAZ class="size-4 text-muted-foreground" />
                    </button>
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-md px-1 py-2 text-left text-sm hover:bg-muted/50"
                        @click="applySort(activeHeaderColumn.key, 'desc')"
                    >
                        <span>Sort Z to A</span>
                        <ArrowDownAZ class="size-4 text-muted-foreground" />
                    </button>
                </div>

                <div class="px-4 py-3">
                    <div class="relative mb-3">
                        <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="headerFilterSearch[activeHeaderColumn.key]"
                            placeholder="Search values..."
                            class="pl-9"
                        />
                    </div>

                    <div class="mb-3 flex items-center justify-between text-xs">
                        <div class="space-x-1">
                            <button
                                type="button"
                                class="font-medium text-primary hover:underline"
                                @click="selectAllColumnValues(activeHeaderColumn.key)"
                            >
                                Select all
                            </button>
                            <span class="text-muted-foreground">-</span>
                            <button
                                type="button"
                                class="font-medium text-primary hover:underline"
                                @click="clearColumnFilter(activeHeaderColumn.key)"
                            >
                                Clear
                            </button>
                        </div>
                        <span class="text-muted-foreground">
                            Showing {{ filteredColumnOptions(activeHeaderColumn.key).length }} values
                            <span v-if="activeFilterCount(activeHeaderColumn.key) > 0">
                                | {{ activeFilterCount(activeHeaderColumn.key) }} selected
                            </span>
                        </span>
                    </div>

                    <div class="max-h-64 overflow-y-auto rounded-lg border border-sidebar-border/70 p-2">
                        <p
                            v-if="filteredColumnOptions(activeHeaderColumn.key).length === 0"
                            class="px-2 py-3 text-sm text-muted-foreground"
                        >
                            No values match your search.
                        </p>
                        <button
                            v-for="value in filteredColumnOptions(activeHeaderColumn.key)"
                            :key="value"
                            type="button"
                            class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-muted/50"
                            @click="toggleColumnValue(activeHeaderColumn.key, value)"
                        >
                            <span class="flex size-4 items-center justify-center">
                                <Check v-if="isValueSelected(activeHeaderColumn.key, value)" class="size-4" />
                            </span>
                            <span class="truncate">{{ value }}</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-sidebar-border/70 px-4 py-3">
                    <Button size="sm" variant="outline" @click="cancelColumnFilter(activeHeaderColumn.key)">
                        Cancel
                    </Button>
                    <Button size="sm" @click="confirmColumnFilter(activeHeaderColumn.key)">
                        OK
                    </Button>
                </div>
            </div>
        </Teleport>
    </div>
</template>
