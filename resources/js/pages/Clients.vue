<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import EntityIndexPage from '@/components/entity/EntityIndexPage.vue';
import type { SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import type { BreadcrumbItem } from '@/types';
import type { ClientItem, ClientsPageProps } from '@/types/client';

const props = defineProps<ClientsPageProps>();

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
] satisfies SpreadsheetColumn[];
</script>

<template>
    <EntityIndexPage
        head-title="Clients"
        title="Clients"
        :rows="rows"
        :columns="clientColumns"
        :breadcrumbs="breadcrumbs"
        description="Data below is loaded from the database and summarizes each client portfolio."
        row-key-field="id"
        show-create-button
        create-label="New Client"
        @create="createClient"
        @row-click="openClient"
    />
</template>
