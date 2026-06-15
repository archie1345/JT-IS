<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import EntityIndexPage from '@/components/entity/EntityIndexPage.vue';
import type { SpreadsheetColumn } from '@/components/shared/DataTable.vue';
import type { BreadcrumbItem } from '@/types';
import type { ProjectItem } from '@/types/project';

const props = defineProps<{
    projects?: ProjectItem[];
    data?: ProjectItem[];
}>();

const rows = computed(() => props.projects ?? props.data ?? []);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Proyek',
        href: '/projects',
    },
];

const openProject = (row: Record<string, unknown>) => {
    const project = row as ProjectItem;
    router.get(`/projects/${project.id}`);
};

const createProject = () => {
    router.get('/projects/create');
};

const getPaymentStatusClass = (status: ProjectItem['paymentStatus']) =>
    ({
        pending: 'bg-amber-500/15 text-amber-500 ring-1 ring-amber-500/25',
        partial: 'bg-sky-500/15 text-sky-500 ring-1 ring-sky-500/25',
        paid: 'bg-emerald-500/15 text-emerald-500 ring-1 ring-emerald-500/25',
        overdue: 'bg-rose-500/15 text-rose-500 ring-1 ring-rose-500/25',
    })[status];

const getProjectStatusClass = (status: ProjectItem['projectStatus']) =>
    ({
        planning: 'bg-slate-500/15 text-slate-500 ring-1 ring-slate-500/25',
        ongoing: 'bg-blue-500/15 text-blue-500 ring-1 ring-blue-500/25',
        completed:
            'bg-emerald-500/15 text-emerald-500 ring-1 ring-emerald-500/25',
    })[status];

const getProjectHealthStatusClass = (status: string) =>
    ({
        'On Track':
            'bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25',
        Warning: 'bg-amber-500/15 text-amber-600 ring-1 ring-amber-500/25',
        Critical: 'bg-rose-500/15 text-rose-600 ring-1 ring-rose-500/25',
        'On Hold': 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/25',
    })[status] ?? 'bg-slate-500/15 text-slate-600 ring-1 ring-slate-500/25';

const formatProjectHealthStatus = (status: string) =>
    ({
        'On Track': 'Sesuai Rencana',
        Warning: 'Perhatian',
        Critical: 'Kritis',
        'On Hold': 'Ditahan',
    })[status] ?? status;

const projectColumns = [
    { key: 'id', label: 'Id' },
    {
        key: 'projectName',
        label: 'Nama Proyek',
        accessor: (row: Record<string, unknown>) =>
            (row as ProjectItem).projectName,
    },
    {
        key: 'estPrice',
        label: 'Estimasi Nilai',
        accessor: (row: Record<string, unknown>) =>
            (row as ProjectItem).estPrice,
    },
    {
        key: 'deadline',
        label: 'Deadline',
        accessor: (row: Record<string, unknown>) =>
            (row as ProjectItem).deadline,
    },
    {
        key: 'paymentStatus',
        label: 'Status Pembayaran',
        accessor: (row: Record<string, unknown>) =>
            ({
                pending: 'Menunggu',
                partial: 'Sebagian',
                paid: 'Lunas',
                overdue: 'Terlambat',
            })[(row as ProjectItem).paymentStatus] ??
            (row as ProjectItem).paymentStatus,
    },
    {
        key: 'projectStatus',
        label: 'Status Data',
        accessor: (row: Record<string, unknown>) =>
            ({
                planning: 'Perencanaan',
                ongoing: 'Berjalan',
                completed: 'Selesai',
            })[(row as ProjectItem).projectStatus] ??
            (row as ProjectItem).projectStatus,
    },
    {
        key: 'projectHealthStatus',
        label: 'Status Kesehatan',
        accessor: (row: Record<string, unknown>) =>
            (row as ProjectItem).projectHealthStatus ?? 'On Track',
    },
] satisfies SpreadsheetColumn[];
</script>

<template>
    <EntityIndexPage
        head-title="Proyek"
        title="Proyek"
        :rows="rows"
        :columns="projectColumns"
        :breadcrumbs="breadcrumbs"
        description="Pantau kontrak aktif, kondisi pembayaran, status proyek, dan level peringatan kesehatan proyek."
        row-key-field="id"
        create-label="Tambah Proyek"
        show-create-button
        @row-click="openProject"
        @create="createProject"
    >
        <template #cell-estPrice="{ value }">
            {{
                new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0,
                }).format(Number(value ?? 0))
            }}
        </template>
        <template #cell-projectHealthStatus="{ value }">
            <span
                class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                :class="
                    getProjectHealthStatusClass(String(value ?? 'On Track'))
                "
            >
                {{ formatProjectHealthStatus(String(value ?? 'On Track')) }}
            </span>
        </template>
    </EntityIndexPage>
</template>
