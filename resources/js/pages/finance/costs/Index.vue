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
    { title: 'Realisasi Biaya', href: '/project-costs' },
];

const uploadConnectionOptions = computed(() =>
    props.records.map((record) => ({
        value: `project_cost:${record.id}`,
        label: `Biaya #${record.id}`,
        hint: String(record.project_name ?? record.category ?? ''),
        componentType: 'project_cost',
        componentId: Number(record.id),
        projectId: Number(record.project_id),
    })),
);

const columns = [
    { key: 'id', label: 'Id' },
    { key: 'project_name', label: 'Proyek' },
    { key: 'reference_number', label: 'No. Referensi' },
    { key: 'category', label: 'Kategori' },
    { key: 'vendor', label: 'Vendor' },
    { key: 'amount', label: 'Nilai' },
    { key: 'date', label: 'Tanggal' },
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
        name: 'reference_number',
        label: 'Nomor Referensi',
        type: 'text',
        placeholder: 'Nomor receipt, PO, atau dokumen vendor',
    },
    {
        name: 'category',
        label: 'Kategori',
        type: 'text',
        placeholder: 'Material, tenaga kerja, transport...',
    },
    {
        name: 'vendor',
        label: 'Vendor / Penerima',
        type: 'text',
        placeholder: 'Contoh: CV. Solusi Mandiri',
    },
    { name: 'amount', label: 'Nilai', type: 'number', min: 0, step: '0.01' },
    { name: 'date', label: 'Tanggal', type: 'date' },
    {
        name: 'description',
        label: 'Deskripsi',
        type: 'textarea',
        placeholder: 'Detail biaya atau catatan pendukung',
    },
] as const;
</script>

<template>
    <CrudPrototypePage
        head-title="Realisasi Biaya"
        title="Realisasi Biaya"
        description="Catat pengeluaran proyek agar manajemen bisa membandingkan realisasi biaya dengan rap."
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
        :pagination="props.pagination"
        create-label="Tambah Biaya"
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
