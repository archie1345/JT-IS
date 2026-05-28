<script setup lang="ts">
import { computed } from 'vue';
import CrudPrototypePage from '@/components/prototype/CrudPrototypePage.vue';
import type { SpreadsheetColumn } from '@/components/shared/DataTable.vue';
import type { BreadcrumbItem } from '@/types';
import type { RapRow } from '@/types/rap';
import type { UploadedDocument } from '@/types/project';

type Option = {
    value: number;
    label: string;
};

type Pagination = {
    currentPage: number;
    lastPage: number;
    maxPerPage?: number;
    perPage: number;
    perPageOptions?: number[];
    total: number;
};

const props = defineProps<{
    raps?: RapRow[];
    data?: RapRow[];
    activeProjectId?: number | null;
    pagination?: Pagination;
    projectOptions: Option[];
    uploadedDocuments: UploadedDocument[];
}>();

const rows = computed(() => props.raps ?? props.data ?? []);
const uploadConnectionOptions = computed(() =>
    rows.value.map((row) => ({
        value: `rap:${row.id}`,
        label: `RAP #${row.id}`,
        hint: row.projectName,
        componentType: 'rap',
        componentId: row.id,
        projectId: row.projectId,
    })),
);

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

const rapColumns = [
    { key: 'id', label: 'Id' },
    {
        key: 'projectName',
        label: 'Project',
        accessor: (row: Record<string, unknown>) => (row as RapRow).projectName,
    },
    { key: 'document_number', label: 'Document No.' },
    { key: 'document_date', label: 'Document Date' },
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
        accessor: (row: Record<string, unknown>) =>
            (row as RapRow).createdAt ?? '-',
    },
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
        name: 'document_number',
        label: 'Document Number',
        type: 'text',
        placeholder: 'RAP or execution document number',
    },
    {
        name: 'document_date',
        label: 'Document Date',
        type: 'date',
    },
    {
        name: 'total_budget',
        label: 'Total Execution Budget',
        type: 'number',
        min: 0,
        step: '0.01',
    },
    {
        name: 'notes',
        label: 'Notes',
        type: 'textarea',
        placeholder: 'Rekapitulasi, deviasi, or document remarks',
    },
] as const;
</script>

<template>
    <CrudPrototypePage
        head-title="RAP"
        title="RAP List"
        description="Plan records grouped by project."
        :rows="rows"
        :columns="rapColumns"
        :fields="fields"
        :breadcrumbs="breadcrumbs"
        create-url="/raps"
        update-url-base="/raps"
        delete-url-base="/raps"
        detail-url-base="/raps"
        upload-component-type="rap"
        :upload-project-id="props.activeProjectId"
        :project-options="props.projectOptions"
        :uploaded-documents="props.uploadedDocuments"
        :upload-connection-options="uploadConnectionOptions"
        :pagination="props.pagination"
        create-label="New RAP"
        :note="
            props.activeProjectId
                ? `Filtered by project ID ${props.activeProjectId}`
                : `Showing ${props.pagination?.total ?? rows.length} RAP record(s)`
        "
    >
        <template #cell-totalBudget="{ value }">
            <span class="font-medium text-foreground">{{
                formatCurrency(Number(value ?? 0))
            }}</span>
        </template>
    </CrudPrototypePage>
</template>
