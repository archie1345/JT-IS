<script setup lang="ts">
import { computed } from 'vue';
import CrudPrototypePage from '@/components/prototype/CrudPrototypePage.vue';
import type { SpreadsheetColumn } from '@/components/shared/DataTable.vue';
import type { BreadcrumbItem } from '@/types';
import type { RabRow } from '@/types/rab';
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
    rabs?: RabRow[];
    data?: RabRow[];
    activeProjectId?: number | null;
    pagination?: Pagination;
    projectOptions: Option[];
    uploadedDocuments: UploadedDocument[];
}>();

const rows = computed(() => props.rabs ?? props.data ?? []);
const uploadConnectionOptions = computed(() =>
    rows.value.map((row) => ({
        value: `rab:${row.id}`,
        label: `RAB #${row.id}`,
        hint: row.projectName,
        componentType: 'rab',
        componentId: row.id,
        projectId: row.projectId,
    })),
);

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

const rabColumns = [
    { key: 'id', label: 'Id' },
    {
        key: 'projectName',
        label: 'Proyek',
        accessor: (row: Record<string, unknown>) => (row as RabRow).projectName,
    },
    { key: 'document_number', label: 'No. Dokumen' },
    { key: 'document_date', label: 'Tanggal Dokumen' },
    {
        key: 'totalBudget',
        label: 'Total Budget',
        accessor: (row: Record<string, unknown>) => (row as RabRow).totalBudget,
    },
    {
        key: 'itemCount',
        label: 'Item',
        accessor: (row: Record<string, unknown>) => (row as RabRow).itemCount,
    },
    {
        key: 'updatedAt',
        label: 'Diupdate',
        accessor: (row: Record<string, unknown>) =>
            (row as RabRow).updatedAt ?? '-',
    },
] satisfies SpreadsheetColumn[];

const fields = [
    {
        name: 'project_id',
        label: 'Proyek',
        type: 'select',
        options: props.projectOptions,
        required: true,
    },
    {
        name: 'document_number',
        label: 'Nomor Dokumen',
        type: 'text',
        placeholder: 'Nomor dokumen RAB atau kontrak',
    },
    {
        name: 'document_date',
        label: 'Tanggal Dokumen',
        type: 'date',
    },
    {
        name: 'total_budget',
        label: 'Total / Nilai Kontrak',
        type: 'number',
        min: 0,
        step: '0.01',
    },
    {
        name: 'notes',
        label: 'Catatan',
        type: 'textarea',
        placeholder: 'Terbilang atau catatan dokumen',
    },
] as const;
</script>

<template>
    <CrudPrototypePage
        head-title="RAB"
        title="Daftar RAB"
        description="Review budget pendapatan proyek dan baseline nilai kontrak untuk tiap proyek."
        :rows="rows"
        :columns="rabColumns"
        :fields="fields"
        :breadcrumbs="breadcrumbs"
        create-url="/rabs"
        update-url-base="/rabs"
        delete-url-base="/rabs"
        detail-url-base="/rabs"
        upload-component-type="rab"
        :upload-project-id="props.activeProjectId"
        :project-options="props.projectOptions"
        :uploaded-documents="props.uploadedDocuments"
        :upload-connection-options="uploadConnectionOptions"
        :pagination="props.pagination"
    >
        <template #cell-totalBudget="{ value }">
            <span class="font-medium text-foreground">{{
                formatCurrency(Number(value ?? 0))
            }}</span>
        </template>
    </CrudPrototypePage>
</template>
