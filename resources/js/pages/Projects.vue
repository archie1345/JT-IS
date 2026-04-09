<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import EntityIndexPage from '@/components/entity/EntityIndexPage.vue';
import type { SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import type { BreadcrumbItem } from '@/types';
import type { ProjectItem, ProjectsPageProps } from '@/types/project';

const props = defineProps<ProjectsPageProps>();

const rows = computed(() => props.projects ?? props.data ?? []);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Projects',
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
        completed: 'bg-emerald-500/15 text-emerald-500 ring-1 ring-emerald-500/25',
    })[status];

const projectColumns = [
    { key: 'id', label: 'Id' },
    { key: 'projectName', label: 'Project Name', accessor: (row: Record<string, unknown>) => (row as ProjectItem).projectName },
    { key: 'client', label: 'Client', accessor: (row: Record<string, unknown>) => (row as ProjectItem).client },
    { key: 'estPrice', label: 'Est. Value', accessor: (row: Record<string, unknown>) => (row as ProjectItem).estPrice },
    { key: 'deadline', label: 'Deadline', accessor: (row: Record<string, unknown>) => (row as ProjectItem).deadline },
    {
        key: 'paymentStatus',
        label: 'Payment Status',
        accessor: (row: Record<string, unknown>) =>
            ({
                pending: 'Pending',
                partial: 'Partial',
                paid: 'Paid',
                overdue: 'Overdue',
            })[(row as ProjectItem).paymentStatus] ?? (row as ProjectItem).paymentStatus,
    },
    {
        key: 'projectStatus',
        label: 'Project Status',
        accessor: (row: Record<string, unknown>) =>
            ({
                planning: 'Planning',
                ongoing: 'Ongoing',
                completed: 'Completed',
        })[(row as ProjectItem).projectStatus] ?? (row as ProjectItem).projectStatus,
    },
] satisfies SpreadsheetColumn[];
</script>

<template>
    <EntityIndexPage
        head-title="Projects"
        title="Projects"
        :rows="rows"
        :columns="projectColumns"
        :breadcrumbs="breadcrumbs"
        description="Project data below is loaded from the database."
        :note="props.activeClientId ? `Filtered by client ID: ${props.activeClientId}` : ''"
        row-key-field="id"
        create-label="New Project"
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
    </EntityIndexPage>
</template>
