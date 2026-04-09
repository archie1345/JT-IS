<script setup lang="ts">
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ProjectDataTable, { type SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import type { BreadcrumbItem } from '@/types';
import type { RabRow } from '@/types/rab';

const props = defineProps<{
    rabs?: RabRow[];
    data?: RabRow[];
    activeProjectId?: number | null;
}>();

const rows = computed(() => props.rabs ?? props.data ?? []);

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

const openProject = (row: Record<string, unknown>) => {
    const item = row as RabRow;
    router.get(`/rabs/${item.id}`);
};

const rabColumns = [
    { key: 'id', label: 'Id' },
    { key: 'projectName', label: 'Project', accessor: (row: Record<string, unknown>) => (row as RabRow).projectName },
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
        accessor: (row: Record<string, unknown>) => (row as RabRow).updatedAt ?? '-',
    },
] satisfies SpreadsheetColumn[];
</script>

<template>
    <Head title="RAB" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-4 rounded-xl p-4">
            <section class="rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-semibold tracking-tight text-foreground">
                            RAB
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Budget plan records grouped by project.
                        </p>
                    </div>

                    <div class="rounded-xl border border-sidebar-border/70 bg-background px-4 py-3 text-sm text-muted-foreground">
                        {{ props.activeProjectId ? `Filtered by project ID ${props.activeProjectId}` : 'All projects' }}
                    </div>
                </div>
            </section>

            <ProjectDataTable
                :rows="rows"
                :columns="rabColumns"
                title="RAB List"
                description="Each row links back to the owning project."
                row-key-field="id"
                :stretch-to-viewport="false"
                empty-text="No RAB records found."
                @row-click="openProject"
            >
                <template #cell-totalBudget="{ value }">
                    <span class="font-medium text-foreground">{{ formatCurrency(Number(value ?? 0)) }}</span>
                </template>
            </ProjectDataTable>
        </div>
    </AppLayout>
</template>
