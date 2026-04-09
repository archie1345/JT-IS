<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ProjectDataTable from '@/components/ProjectDataTable.vue';
import type { EntityIndexPageProps, EntityTableRow } from '@/types/entity';

const props = withDefaults(defineProps<EntityIndexPageProps>(), {
    rowKeyField: 'id',
    createLabel: 'New Item',
    emptyText: 'No matching data found.',
    stretchToViewport: true,
    showCreateButton: false,
});

const emit = defineEmits<{
    rowClick: [row: EntityTableRow];
    create: [];
}>();
</script>

<template>
    <Head :title="props.headTitle" />

    <AppLayout :breadcrumbs="props.breadcrumbs">
        <ProjectDataTable
            :rows="props.rows"
            :columns="props.columns"
            :title="props.title"
            :description="props.description"
            :note="props.note"
            :row-key-field="props.rowKeyField"
            :create-label="props.createLabel"
            :empty-text="props.emptyText"
            :stretch-to-viewport="props.stretchToViewport"
            :show-create-button="props.showCreateButton"
            @row-click="emit('rowClick', $event)"
            @create="emit('create')"
        >
            <template
                v-for="(_, slotName) in $slots"
                :key="String(slotName)"
                #[slotName]="slotProps"
            >
                <slot :name="slotName" v-bind="slotProps" />
            </template>
        </ProjectDataTable>
    </AppLayout>
</template>
