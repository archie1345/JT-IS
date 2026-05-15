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
        total: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Cost Realization', href: '/project-costs' },
];

const columns = [
    { key: 'id', label: 'Id' },
    { key: 'project_name', label: 'Project' },
    { key: 'client_name', label: 'Client' },
    { key: 'category', label: 'Category' },
    { key: 'amount', label: 'Amount' },
    { key: 'date', label: 'Date' },
] satisfies SpreadsheetColumn[];

const fields = [
    { name: 'project_id', label: 'Project', type: 'select', options: props.projectOptions, required: true },
    { name: 'category', label: 'Category', type: 'text', placeholder: 'Material, labor, transport...' },
    { name: 'amount', label: 'Amount', type: 'number', min: 0, step: '0.01' },
    { name: 'date', label: 'Date', type: 'date' },
] as const;
</script>

<template>
    <CrudPrototypePage
        head-title="Cost Realization"
        title="Cost Realization"
        description="Track realized project costs with simple create, edit, and delete actions."
        :breadcrumbs="breadcrumbs"
        :rows="props.records"
        :columns="columns"
        :fields="fields"
        create-url="/project-costs"
        update-url-base="/project-costs"
        delete-url-base="/project-costs"
        create-label="New Cost Entry"
        :note="`Showing ${props.pagination.total} realized cost record(s)`"
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
