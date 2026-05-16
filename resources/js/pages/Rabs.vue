<script setup lang="ts">
import { computed } from 'vue';
import CrudPrototypePage from '@/components/prototype/CrudPrototypePage.vue';
import type { SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import type { BreadcrumbItem } from '@/types';
import type { RabRow } from '@/types/rab';
import type { UploadedDocument } from '@/types/project';

type Option = {
    value: number;
    label: string;
};

const props = defineProps<{
    rabs?: RabRow[];
    data?: RabRow[];
    activeProjectId?: number | null;
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
        label: 'Project',
        accessor: (row: Record<string, unknown>) => (row as RabRow).projectName,
    },
    {
        key: 'totalBudget',
        label: 'Total Budget',
        accessor: (row: Record<string, unknown>) => (row as RabRow).totalBudget,
    },
    {
        key: 'itemCount',
        label: 'Items',
        accessor: (row: Record<string, unknown>) => (row as RabRow).itemCount,
    },
    {
        key: 'updatedAt',
        label: 'Updated',
        accessor: (row: Record<string, unknown>) =>
            (row as RabRow).updatedAt ?? '-',
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
        name: 'total_budget',
        label: 'Total Budget',
        type: 'number',
        min: 0,
        step: '0.01',
    },
] as const;
</script>

<template>
    <CrudPrototypePage
        head-title="RAB"
        title="RAB List"
        description="Budget plan records grouped by project."
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
        create-label="New RAB"
        :note="
            props.activeProjectId
                ? `Filtered by project ID ${props.activeProjectId}`
                : `${rows.length} RAB record(s)`
        "
    >
        <template #cell-totalBudget="{ value }">
            <span class="font-medium text-foreground">{{
                formatCurrency(Number(value ?? 0))
            }}</span>
        </template>
    </CrudPrototypePage>
</template>
