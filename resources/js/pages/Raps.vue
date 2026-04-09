<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import EntityIndexPage from '@/components/entity/EntityIndexPage.vue';
import type { SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import type { BreadcrumbItem } from '@/types';
import type { RapRow } from '@/types/rap';

const props = defineProps<{
    raps?: RapRow[];
    data?: RapRow[];
    activeProjectId?: number | null;
}>();

const rows = computed(() => props.raps ?? props.data ?? []);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'RAP',
        href: '/raps',
    },
];

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const openProject = (row: Record<string, unknown>) => {
    const item = row as RapRow;
    router.get(`/raps/${item.id}`);
};

const rapColumns = [
    { key: 'id', label: 'Id' },
    { key: 'projectName', label: 'Project', accessor: (row: Record<string, unknown>) => (row as RapRow).projectName },
    {
        key: 'totalBudget',
        label: 'Total Budget',
        accessor: (row: Record<string, unknown>) => (row as RapRow).totalBudget,
    },
    {
        key: 'itemCount',
        label: 'Items',
        accessor: (row: Record<string, unknown>) => (row as RapRow).itemCount,
    },
    {
        key: 'createdAt',
        label: 'Created',
        accessor: (row: Record<string, unknown>) => (row as RapRow).createdAt ?? '-',
    },
] satisfies SpreadsheetColumn[];
</script>

<template>
    <EntityIndexPage
        head-title="RAP"
        title="RAP List"
        :rows="rows"
        :columns="rapColumns"
        :breadcrumbs="breadcrumbs"
        intro-title="RAP"
        intro-description="Plan records grouped by project."
        :intro-badge="props.activeProjectId ? `Filtered by project ID ${props.activeProjectId}` : 'All projects'"
        description="Each row links back to the owning project."
        row-key-field="id"
        :stretch-to-viewport="false"
        empty-text="No RAP records found."
        @row-click="openProject"
    >
        <template #cell-totalBudget="{ value }">
            <span class="font-medium text-foreground">{{ formatCurrency(Number(value ?? 0)) }}</span>
        </template>
    </EntityIndexPage>
</template>
