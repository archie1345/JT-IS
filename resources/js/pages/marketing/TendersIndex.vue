<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ArrowRightCircle } from 'lucide-vue-next';
import CrudPrototypePage from '@/components/prototype/CrudPrototypePage.vue';
import type { SpreadsheetColumn } from '@/components/shared/DataTable.vue';
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Marketing Pipeline', href: '/pipeline' },
];

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
    { key: 'project_name', label: 'Proyek' },
    { key: 'document_number', label: 'No. Dokumen' },
    { key: 'document_date', label: 'Tanggal Dokumen' },
    { key: 'title', label: 'Tender / Laporan' },
    { key: 'owner', label: 'Owner' },
    { key: 'value', label: 'Estimasi Nilai' },
    { key: 'status', label: 'Status' },
] satisfies SpreadsheetColumn[];

const fields = [
    {
        name: 'project_id',
        label: 'Proyek',
        type: 'select',
        options: props.projectOptions,
    },
    {
        name: 'document_number',
        label: 'Nomor Dokumen',
        type: 'text',
        placeholder: 'Contoh: 001/SPH/JTE/II/2026',
    },
    {
        name: 'document_date',
        label: 'Tanggal Dokumen',
        type: 'date',
    },
    {
        name: 'title',
        label: 'Nama Pekerjaan / Paket',
        type: 'text',
        placeholder: 'Contoh: Konsolidasi pembangunan perkuatan tanggul',
    },
    {
        name: 'owner',
        label: 'Owner',
        type: 'text',
        placeholder: 'Contoh: Perum Jasa Tirta I',
    },
    {
        name: 'location',
        label: 'Lokasi',
        type: 'textarea',
        placeholder: 'Lokasi proyek dari dokumen',
    },
    {
        name: 'value',
        label: 'Nilai Penawaran / Kontrak',
        type: 'number',
        min: 0,
        step: '0.01',
    },
    {
        name: 'status',
        label: 'Status',
        type: 'select',
        options: [
            { value: 'open', label: 'Terbuka' },
            { value: 'submitted', label: 'Diajukan' },
            { value: 'won', label: 'Menang' },
            { value: 'lost', label: 'Kalah' },
        ],
    },
    {
        name: 'notes',
        label: 'Catatan',
        type: 'textarea',
        placeholder: 'Scope, catatan addendum, atau catatan dokumen',
    },
] as const;

const convertTender = (record: Record<string, null | number | string>) => {
    router.post(
        `/pipeline/${Number(record.id)}/convert`,
        {},
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <CrudPrototypePage
        head-title="Marketing Pipeline"
        title="Marketing Pipeline"
        description="Pantau peluang tender dari penawaran terbuka sampai diajukan, menang, atau kalah."
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
        create-label="Tambah Tender"
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
                title="Konversi ke Proyek"
                @click="
                    convertTender(row as Record<string, null | number | string>)
                "
            >
                <ArrowRightCircle class="size-4" />
            </Button>
        </template>
    </CrudPrototypePage>
</template>
