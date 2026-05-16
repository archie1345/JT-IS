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
    { key: 'progress_percent', label: 'Progress %' },
    { key: 'report_date', label: 'Report Date' },
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
        name: 'progress_percent',
        label: 'Progress Percent',
        type: 'number',
        min: 0,
        max: 100,
        step: '1',
    },
    { name: 'report_date', label: 'Report Date', type: 'date' },
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
        upload-component-type="progress_report"
        :project-options="props.projectOptions"
        :uploaded-documents="props.uploadedDocuments"
        :upload-connection-options="uploadConnectionOptions"
        create-label="New Progress Update"
        :note="`Showing ${props.pagination.total} progress report(s)`"
    />
</template>
