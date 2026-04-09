<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import EntityIndexPage from '@/components/entity/EntityIndexPage.vue';
import type { SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import type { BreadcrumbItem } from '@/types';
import type { RabRow } from '@/types/rab';

const props = defineProps<{
    rabs?: RabRow[];
    data?: RabRow[];
    activeProjectId?: number | null;
}>();

const rows = computed(() => props.rabs ?? props.data ?? []);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'RAB',
        href: '/rabs',
    },
];

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const openProject = (row: Record<string, unknown>) => {
    const item = row as RabRow;
    router.get(`/rabs/${item.id}`);
};

const rabColumns = [
    { key: 'id', label: 'Id' },
    { key: 'projectName', label: 'Project', accessor: (row: Record<string, unknown>) => (row as RabRow).projectName },
    {
        key: 'totalBudget',
        label: 'Total Budget',
        accessor: (row: Record<string, unknown>) => (row as RabRow).totalBudget,
    },
    {
        key: 'itemCount',
        label: 'Items',
        accessor: (row: Record<string, unknown>) => (row as RabRow).itemCount,
    },
    {
        key: 'updatedAt',
        label: 'Updated',
        accessor: (row: Record<string, unknown>) => (row as RabRow).updatedAt ?? '-',
    },
] satisfies SpreadsheetColumn[];
</script>

<template>
    <EntityIndexPage
        head-title="RAB"
        title="RAB List"
        :rows="rows"
        :columns="rabColumns"
        :breadcrumbs="breadcrumbs"
        intro-title="RAB"
        intro-description="Budget plan records grouped by project."
        :intro-badge="props.activeProjectId ? `Filtered by project ID ${props.activeProjectId}` : 'All projects'"
        description="Each row links back to the owning project."
        row-key-field="id"
        :stretch-to-viewport="false"
        empty-text="No RAB records found."
        @row-click="openProject"
    >
        <template #cell-totalBudget="{ value }">
            <span class="font-medium text-foreground">{{ formatCurrency(Number(value ?? 0)) }}</span>
        </template>
    </EntityIndexPage>
</template>
