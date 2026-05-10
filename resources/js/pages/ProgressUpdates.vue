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
    { title: 'Progress Update', href: '/progress-updates' },
];

const columns = [
    { key: 'id', label: 'Id' },
    { key: 'project_name', label: 'Project' },
    { key: 'client_name', label: 'Client' },
    { key: 'progress_percent', label: 'Progress %' },
    { key: 'report_date', label: 'Report Date' },
    { key: 'description', label: 'Summary' },
] satisfies SpreadsheetColumn[];

const fields = [
    { name: 'project_id', label: 'Project', type: 'select', options: props.projectOptions, required: true },
    { name: 'progress_percent', label: 'Progress Percent', type: 'number', min: 0, max: 100, step: '1' },
    { name: 'report_date', label: 'Report Date', type: 'date' },
    { name: 'description', label: 'Summary', type: 'textarea', placeholder: 'What happened in this update?' },
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
        create-label="New Progress Update"
        :note="`Showing ${props.pagination.total} progress report(s)`"
    />
</template>
