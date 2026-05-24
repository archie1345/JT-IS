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
        currentPage: number;
        lastPage: number;
        maxPerPage?: number;
        perPage: number;
        perPageOptions?: number[];
        total: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Progress Update', href: '/progress-updates' },
];

const uploadConnectionOptions = computed(() =>
    props.records.map((record) => ({
        value: `progress_report:${record.id}`,
        label: `Progress #${record.id}`,
        hint: String(record.project_name ?? record.report_date ?? ''),
        componentType: 'progress_report',
        componentId: Number(record.id),
        projectId: Number(record.project_id),
    })),
);

const columns = [
    { key: 'id', label: 'Id' },
    { key: 'project_name', label: 'Project' },
    { key: 'client_name', label: 'Client' },
    { key: 'document_number', label: 'Document No.' },
    { key: 'document_type', label: 'Document Type' },
    { key: 'progress_percent', label: 'Progress %' },
    { key: 'period_start', label: 'Period Start' },
    { key: 'period_end', label: 'Period End' },
    { key: 'report_date', label: 'Report Date' },
    { key: 'approved_by_client', label: 'Client OK' },
    { key: 'approved_by_internal', label: 'Internal OK' },
    { key: 'description', label: 'Summary' },
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
        placeholder: 'BA/MC/C3 document number',
    },
    {
        name: 'document_type',
        label: 'Document Type',
        type: 'select',
        options: [
            { value: 'BA MC', label: 'BA MC / Mutual Check' },
            { value: 'BAHPP', label: 'BAHPP' },
            { value: 'C3', label: 'C3' },
            { value: 'Laporan Akhir', label: 'Laporan Akhir' },
        ],
    },
    {
        name: 'progress_percent',
        label: 'Progress Percent',
        type: 'number',
        min: 0,
        max: 100,
        step: '0.01',
    },
    { name: 'period_start', label: 'Period Start', type: 'date' },
    { name: 'period_end', label: 'Period End', type: 'date' },
    { name: 'report_date', label: 'Report Date', type: 'date' },
    {
        name: 'approved_by_client',
        label: 'Client Approval',
        type: 'select',
        options: [
            { value: '0', label: 'No' },
            { value: '1', label: 'Yes' },
        ],
    },
    {
        name: 'approved_by_internal',
        label: 'Internal Approval',
        type: 'select',
        options: [
            { value: '0', label: 'No' },
            { value: '1', label: 'Yes' },
        ],
    },
    {
        name: 'description',
        label: 'Summary',
        type: 'textarea',
        placeholder: 'What happened in this update?',
    },
] as const;
</script>

<template>
    <CrudPrototypePage
        head-title="Progress Update"
        title="Progress Update"
        description="Prototype progress reporting page backed by the existing progress_reports table."
        :breadcrumbs="breadcrumbs"
        :rows="props.records"
        :columns="columns"
        :fields="fields"
        create-url="/progress-updates"
        update-url-base="/progress-updates"
        delete-url-base="/progress-updates"
        detail-url-base="/progress-updates"
        upload-component-type="progress_report"
        :project-options="props.projectOptions"
        :uploaded-documents="props.uploadedDocuments"
        :upload-connection-options="uploadConnectionOptions"
        :pagination="props.pagination"
        create-label="New Progress Update"
        :note="`Showing ${props.pagination.total} progress report(s)`"
    >
        <template #cell-approved_by_client="{ value }">
            {{ value === '1' || value === 1 ? 'Yes' : 'No' }}
        </template>
        <template #cell-approved_by_internal="{ value }">
            {{ value === '1' || value === 1 ? 'Yes' : 'No' }}
        </template>
    </CrudPrototypePage>
</template>
