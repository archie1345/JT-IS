<script setup lang="ts">
import { computed } from 'vue';
import CrudPrototypePage from '@/components/prototype/CrudPrototypePage.vue';
import type { SpreadsheetColumn } from '@/components/shared/DataTable.vue';
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
    { title: 'Progress / BAMC', href: '/progress-updates' },
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
    { key: 'project_name', label: 'Proyek' },
    { key: 'client_name', label: 'Klien' },
    { key: 'document_number', label: 'No. Dokumen' },
    { key: 'document_type', label: 'Jenis Dokumen' },
    { key: 'progress_percent', label: 'Progress %' },
    { key: 'period_start', label: 'Awal Periode' },
    { key: 'period_end', label: 'Akhir Periode' },
    { key: 'report_date', label: 'Tanggal Laporan' },
    { key: 'approved_by_client', label: 'Klien OK' },
    { key: 'approved_by_internal', label: 'Internal OK' },
    { key: 'description', label: 'Ringkasan' },
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
        placeholder: 'Nomor dokumen BA/MC/C3',
    },
    {
        name: 'document_type',
        label: 'Jenis Dokumen',
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
        label: 'Persentase Progress',
        type: 'number',
        min: 0,
        max: 100,
        step: '0.01',
    },
    { name: 'period_start', label: 'Awal Periode', type: 'date' },
    { name: 'period_end', label: 'Akhir Periode', type: 'date' },
    { name: 'report_date', label: 'Tanggal Laporan', type: 'date' },
    {
        name: 'approved_by_client',
        label: 'Approval Klien',
        type: 'select',
        options: [
            { value: '0', label: 'Tidak' },
            { value: '1', label: 'Ya' },
        ],
    },
    {
        name: 'approved_by_internal',
        label: 'Approval Internal',
        type: 'select',
        options: [
            { value: '0', label: 'Tidak' },
            { value: '1', label: 'Ya' },
        ],
    },
    {
        name: 'description',
        label: 'Ringkasan',
        type: 'textarea',
        placeholder: 'Apa yang terjadi pada update ini?',
    },
] as const;
</script>

<template>
    <CrudPrototypePage
        head-title="Progress / BAMC"
        title="Progress / BAMC"
        description="Catat milestone progress fisik dan approval klien/internal yang digunakan untuk tagihan resmi."
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
        create-label="Tambah Laporan Progress"
    >
        <template #cell-approved_by_client="{ value }">
            {{ value === '1' || value === 1 ? 'Ya' : 'Tidak' }}
        </template>
        <template #cell-approved_by_internal="{ value }">
            {{ value === '1' || value === 1 ? 'Ya' : 'Tidak' }}
        </template>
    </CrudPrototypePage>
</template>
