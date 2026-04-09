<script setup lang="ts">
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ProjectDataTable from '@/components/ProjectDataTable.vue';
import type { BreadcrumbItem } from '@/types';
import type { ClientItem } from '@/types/client';

const props = defineProps<{
    clients?: ClientItem[];
    data?: ClientItem[];
}>();

const rows = computed(() => props.clients ?? props.data ?? []);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clients',
        href: '/client',
    },
];

const openClient = (row: Record<string, unknown>) => {
    const item = row as ClientItem;
    router.get(`/client/${item.id}`);
};

const createClient = () => {
    router.get('/client/create');
};

const clientColumns = [
    { key: 'id', label: 'Id' },
    { key: 'name', label: 'Client Name', accessor: (row: Record<string, unknown>) => (row as ClientItem).name ?? '-' },
    { key: 'contact', label: 'Contact', accessor: (row: Record<string, unknown>) => (row as ClientItem).contact ?? '-' },
    { key: 'projectCount', label: 'Projects', accessor: (row: Record<string, unknown>) => (row as ClientItem).projectCount },
];
</script>

<template>
    <Head title="Clients" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <ProjectDataTable
            :rows="rows"
            :columns="clientColumns"
            title="Clients"
            description="Data below is loaded from the database and summarizes each client portfolio."
            row-key-field="id"
            show-create-button
            create-label="New Client"
            @create="createClient"
            @row-click="openClient"
        >
        </ProjectDataTable>
    </AppLayout>
</template>
