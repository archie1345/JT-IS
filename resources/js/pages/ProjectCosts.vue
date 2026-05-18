<script setup lang="ts">
import { computed } from 'vue';
import CrudPrototypePage from '@/components/prototype/CrudPrototypePage.vue';
import type { SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import type { BreadcrumbItem } from '@/types';
import type { UploadedDocument } from '@/types/project';

type Option = {
    value: number;
    label: string;
    hint?: null | string;
};

const props = defineProps<{
    records: Record<string, null | number | string>[];
    projectOptions: Option[];
    uploadedDocuments: UploadedDocument[];
    pagination: {
        total: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Cost Realization', href: '/project-costs' },
];

const uploadConnectionOptions = computed(() =>
    props.records.map((record) => ({
        value: `project_cost:${record.id}`,
        label: `Cost #${record.id}`,
        hint: String(record.project_name ?? record.category ?? ''),
        componentType: 'project_cost',
        componentId: Number(record.id),
        projectId: Number(record.project_id),
    })),
);

const columns = [
    { key: 'id', label: 'Id' },
    { key: 'project_name', label: 'Project' },
    { key: 'client_name', label: 'Client' },
    { key: 'reference_number', label: 'Reference No.' },
    { key: 'category', label: 'Category' },
    { key: 'vendor', label: 'Vendor' },
    { key: 'amount', label: 'Amount' },
    { key: 'date', label: 'Date' },
] satisfies SpreadsheetColumn[];

const fields = [
    {
        name: 'project_id',
        label: 'Project',
        type: 'select',
        options: props.projectOptions,
        required: true,
    },
    {
        name: 'reference_number',
        label: 'Reference Number',
        type: 'text',
        placeholder: 'Receipt, PO, or vendor document number',
    },
    {
        name: 'category',
        label: 'Category',
        type: 'text',
        placeholder: 'Material, labor, transport...',
    },
    {
        name: 'vendor',
        label: 'Vendor / Payee',
        type: 'text',
        placeholder: 'Example: CV. Solusi Mandiri',
    },
    { name: 'amount', label: 'Amount', type: 'number', min: 0, step: '0.01' },
    { name: 'date', label: 'Date', type: 'date' },
    {
        name: 'description',
        label: 'Description',
        type: 'textarea',
        placeholder: 'Cost detail or supporting note',
    },
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
        detail-url-base="/project-costs"
        upload-component-type="project_cost"
        :project-options="props.projectOptions"
        :uploaded-documents="props.uploadedDocuments"
        :upload-connection-options="uploadConnectionOptions"
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
