<script setup lang="ts">
import EntityIndexPage from '@/components/entity/EntityIndexPage.vue';
import type { SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    records: Record<string, null | number | string>[];
    totals: {
        contracts: number;
        invoiced: number;
        costs: number;
        profit: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Profit and Loss', href: '/profit-loss' },
];

const columns = [
    { key: 'project_name', label: 'Project' },
    { key: 'client_name', label: 'Client' },
    { key: 'status', label: 'Status' },
    { key: 'contract_value', label: 'Contract Value' },
    { key: 'invoice_total', label: 'Invoiced' },
    { key: 'cost_total', label: 'Costs' },
    { key: 'gross_profit', label: 'Gross Profit' },
    { key: 'margin_percent', label: 'Margin %' },
] satisfies SpreadsheetColumn[];

const formatCurrency = (value: unknown) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));
</script>

<template>
    <EntityIndexPage
        head-title="Profit and Loss"
        title="Profit and Loss"
        :rows="props.records"
        :columns="columns"
        :breadcrumbs="breadcrumbs"
        description="Read-only profitability prototype derived from projects, invoices, and realized costs."
        :note="`Contracts ${formatCurrency(props.totals.contracts)} | Invoiced ${formatCurrency(props.totals.invoiced)} | Costs ${formatCurrency(props.totals.costs)} | Profit ${formatCurrency(props.totals.profit)}`"
    >
        <template #cell-contract_value="{ value }">{{ formatCurrency(value) }}</template>
        <template #cell-invoice_total="{ value }">{{ formatCurrency(value) }}</template>
        <template #cell-cost_total="{ value }">{{ formatCurrency(value) }}</template>
        <template #cell-gross_profit="{ value }">{{ formatCurrency(value) }}</template>
        <template #cell-margin_percent="{ value }">{{ Number(value ?? 0).toFixed(1) }}%</template>
    </EntityIndexPage>
</template>
