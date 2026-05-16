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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Reports', href: '/pipeline' }];

const uploadConnectionOptions = computed(() =>
    props.records.map((record) => ({
        value: `pipeline:${record.id}`,
        label: `Pipeline #${record.id}`,
        hint: String(record.title ?? ''),
        componentType: 'pipeline',
        componentId: Number(record.id),
        projectId: null,
    })),
);

const columns = [
    { key: 'id', label: 'Id' },
    { key: 'title', label: 'Tender / Report' },
    { key: 'value', label: 'Estimated Value' },
    { key: 'status', label: 'Status' },
    { key: 'created_at', label: 'Created' },
] satisfies SpreadsheetColumn[];

const fields = [
    {
        name: 'title',
        label: 'Tender / Report Title',
        type: 'text',
        placeholder: 'Example: Bid package A',
    },
    {
        name: 'value',
        label: 'Estimated Value',
        type: 'number',
        min: 0,
        step: '0.01',
    },
    {
        name: 'status',
        label: 'Status',
        type: 'select',
        options: [
            { value: 'open', label: 'Open' },
            { value: 'submitted', label: 'Submitted' },
            { value: 'won', label: 'Won' },
            { value: 'lost', label: 'Lost' },
        ],
    },
] as const;
</script>

<template>
    <CrudPrototypePage
        head-title="Reports"
        title="Reports"
        description="Simple marketing pipeline prototype backed by the tenders table."
        :breadcrumbs="breadcrumbs"
        :rows="props.records"
        :columns="columns"
        :fields="fields"
        create-url="/pipeline"
        update-url-base="/pipeline"
        delete-url-base="/pipeline"
        upload-component-type="pipeline"
        :project-options="props.projectOptions"
        :uploaded-documents="props.uploadedDocuments"
        :upload-connection-options="uploadConnectionOptions"
        create-label="New Pipeline Item"
        :note="`Showing ${props.pagination.total} pipeline item(s)`"
    >
        <template #cell-value="{ value }">
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
