<script setup lang="ts">
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ProjectDataTable, { type SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import type { BreadcrumbItem } from '@/types';
import type { RapRow } from '@/types/rap';

const props = defineProps<{
    raps?: RapRow[];
    data?: RapRow[];
    activeProjectId?: number | null;
}>();

const rows = computed(() => props.raps ?? props.data ?? []);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'RAP',
        href: '/raps',
    },
];

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const openProject = (row: Record<string, unknown>) => {
    const item = row as RapRow;
    router.get(`/raps/${item.id}`);
};

const rapColumns = [
    { key: 'id', label: 'Id' },
    { key: 'projectName', label: 'Project', accessor: (row: Record<string, unknown>) => (row as RapRow).projectName },
    {
        key: 'totalBudget',
        label: 'Total Budget',
        accessor: (row: Record<string, unknown>) => (row as RapRow).totalBudget,
    },
    {
        key: 'itemCount',
        label: 'Items',
        accessor: (row: Record<string, unknown>) => (row as RapRow).itemCount,
    },
    {
        key: 'createdAt',
        label: 'Created',
        accessor: (row: Record<string, unknown>) => (row as RapRow).createdAt ?? '-',
    },
] satisfies SpreadsheetColumn[];
</script>

<template>
    <Head title="RAP" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-4 rounded-xl p-4">
            <section class="rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-semibold tracking-tight text-foreground">
                            RAP
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Plan records grouped by project.
                        </p>
                    </div>

                    <div class="rounded-xl border border-sidebar-border/70 bg-background px-4 py-3 text-sm text-muted-foreground">
                        {{ props.activeProjectId ? `Filtered by project ID ${props.activeProjectId}` : 'All projects' }}
                    </div>
                </div>
            </section>

            <ProjectDataTable
                :rows="rows"
                :columns="rapColumns"
                title="RAP List"
                description="Each row links back to the owning project."
                row-key-field="id"
                :stretch-to-viewport="false"
                empty-text="No RAP records found."
                @row-click="openProject"
            >
                <template #cell-totalBudget="{ value }">
                    <span class="font-medium text-foreground">{{ formatCurrency(Number(value ?? 0)) }}</span>
                </template>
            </ProjectDataTable>
        </div>
    </AppLayout>
</template>
