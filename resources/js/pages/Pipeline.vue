<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowRightCircle } from 'lucide-vue-next';
import CrudPrototypePage from '@/components/prototype/CrudPrototypePage.vue';
import type { SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import { Button } from '@/components/ui/button';
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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Reports', href: '/pipeline' }];

const uploadConnectionOptions = computed(() =>
    props.records.map((record) => ({
        value: `pipeline:${record.id}`,
        label: `Pipeline #${record.id}`,
        hint: String(record.document_number ?? record.title ?? ''),
        componentType: 'pipeline',
        componentId: Number(record.id),
        projectId:
            record.project_id === null || record.project_id === undefined
                ? null
                : Number(record.project_id),
    })),
);

const columns = [
    { key: 'id', label: 'Id' },
    { key: 'project_name', label: 'Project' },
    { key: 'document_number', label: 'Document No.' },
    { key: 'document_date', label: 'Document Date' },
    { key: 'title', label: 'Tender / Report' },
    { key: 'owner', label: 'Owner' },
    { key: 'value', label: 'Estimated Value' },
    { key: 'status', label: 'Status' },
] satisfies SpreadsheetColumn[];

const fields = [
    {
        name: 'project_id',
        label: 'Project',
        type: 'select',
        options: props.projectOptions,
    },
    {
        name: 'document_number',
        label: 'Document Number',
        type: 'text',
        placeholder: 'Example: 001/SPH/JTE/II/2026',
    },
    {
        name: 'document_date',
        label: 'Document Date',
        type: 'date',
    },
    {
        name: 'title',
        label: 'Work / Package Title',
        type: 'text',
        placeholder: 'Example: Konsolidasi pembangunan perkuatan tanggul',
    },
    {
        name: 'owner',
        label: 'Owner / Client',
        type: 'text',
        placeholder: 'Example: Perum Jasa Tirta I',
    },
    {
        name: 'location',
        label: 'Location',
        type: 'textarea',
        placeholder: 'Project location from the document',
    },
    {
        name: 'value',
        label: 'Offer / Contract Value',
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
    {
        name: 'notes',
        label: 'Notes',
        type: 'textarea',
        placeholder: 'Scope, addendum notes, or document remarks',
    },
] as const;

const convertTender = (record: Record<string, null | number | string>) => {
    router.post(`/pipeline/${Number(record.id)}/convert`, {}, {
        preserveScroll: true,
    });
};
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
        detail-url-base="/pipeline"
        upload-component-type="pipeline"
        :project-options="props.projectOptions"
        :uploaded-documents="props.uploadedDocuments"
        :upload-connection-options="uploadConnectionOptions"
        :pagination="props.pagination"
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

        <template #cell-status="{ value }">
            <span
                class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                :class="
                    value === 'won'
                        ? 'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25'
                        : value === 'lost'
                          ? 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-500/25'
                          : 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-500/25'
                "
            >
                {{ value ?? '-' }}
            </span>
        </template>

        <template #row-extra-actions="{ row }">
            <Button
                v-if="Number(row.can_convert ?? 0) === 1"
                variant="outline"
                size="icon-sm"
                title="Convert to Project"
                @click="convertTender(row as Record<string, null | number | string>)"
            >
                <ArrowRightCircle class="size-4" />
            </Button>
        </template>
    </CrudPrototypePage>
</template>
