<script setup lang="ts">
import CrudPrototypePage from '@/components/prototype/CrudPrototypePage.vue';
import type { SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import type { BreadcrumbItem } from '@/types';

type Option = {
    value: number;
    label: string;
    hint?: null | string;
};

const props = defineProps<{
    records: Record<string, null | number | string>[];
    projectOptions: Option[];
    pagination: {
        currentPage: number;
        lastPage: number;
        perPage: number;
        total: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Billing', href: '/invoices' },
];

const columns = [
    { key: 'id', label: 'Id' },
    { key: 'project_name', label: 'Project' },
    { key: 'client_name', label: 'Client' },
    { key: 'amount', label: 'Amount' },
    { key: 'invoice_date', label: 'Invoice Date' },
    { key: 'status', label: 'Status' },
] satisfies SpreadsheetColumn[];

const fields = [
    { name: 'project_id', label: 'Project', type: 'select', options: props.projectOptions, required: true },
    { name: 'amount', label: 'Amount', type: 'number', min: 0, step: '0.01' },
    { name: 'invoice_date', label: 'Invoice Date', type: 'date' },
    {
        name: 'status',
        label: 'Status',
        type: 'select',
        options: [
            { value: 'pending', label: 'Pending' },
            { value: 'paid', label: 'Paid' },
            { value: 'overdue', label: 'Overdue' },
        ],
    },
] as const;
</script>

<template>
    <CrudPrototypePage
        head-title="Billing"
        title="Billing"
        description="Simple invoice CRUD prototype using the existing invoices table."
        :breadcrumbs="breadcrumbs"
        :rows="props.records"
        :columns="columns"
        :fields="fields"
        create-url="/invoices"
        update-url-base="/invoices"
        delete-url-base="/invoices"
        create-label="New Invoice"
        :note="`Showing ${props.pagination.total} invoice record(s)`"
    >
        <template #cell-amount="{ value }">
            {{
                value === null
                    ? '-'
                    : new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0,
                    }).format(Number(value))
            }}
        </template>
    </CrudPrototypePage>
</template>
