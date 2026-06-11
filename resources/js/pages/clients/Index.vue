<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import EntityIndexPage from '@/components/entity/EntityIndexPage.vue';
import type { SpreadsheetColumn } from '@/components/shared/DataTable.vue';
import type { BreadcrumbItem } from '@/types';
import type { ClientItem } from '@/types/client';

const props = defineProps<{
    clients?: ClientItem[];
    data?: ClientItem[];
}>();

const rows = computed(() => props.clients ?? props.data ?? []);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Klien',
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
    {
        key: 'name',
        label: 'Nama Klien',
        accessor: (row: Record<string, unknown>) =>
            (row as ClientItem).name ?? '-',
    },
    {
        key: 'contact',
        label: 'Contact',
        accessor: (row: Record<string, unknown>) =>
            (row as ClientItem).contact ?? '-',
    },
    {
        key: 'projectCount',
        label: 'Proyek',
        accessor: (row: Record<string, unknown>) =>
            (row as ClientItem).projectCount,
    },
] satisfies SpreadsheetColumn[];
</script>

<template>
    <EntityIndexPage
        head-title="Klien"
        title="Klien"
        :rows="rows"
        :columns="clientColumns"
        :breadcrumbs="breadcrumbs"
        description="Review kontak klien dan proyek yang terhubung ke tiap akun."
        row-key-field="id"
        show-create-button
        create-label="Tambah Klien"
        @create="createClient"
        @row-click="openClient"
    />
</template>
