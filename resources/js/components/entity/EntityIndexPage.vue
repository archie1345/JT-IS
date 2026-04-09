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
        <div class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-4 rounded-xl p-4">
            <section
                v-if="props.introTitle || props.introDescription || props.introBadge"
                class="rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 v-if="props.introTitle" class="text-3xl font-semibold tracking-tight text-foreground">
                            {{ props.introTitle }}
                        </h1>
                        <p v-if="props.introDescription" class="text-sm text-muted-foreground">
                            {{ props.introDescription }}
                        </p>
                    </div>

                    <div
                        v-if="props.introBadge"
                        class="rounded-xl border border-sidebar-border/70 bg-background px-4 py-3 text-sm text-muted-foreground"
                    >
                        {{ props.introBadge }}
                    </div>
                </div>
            </section>

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
        </div>
    </AppLayout>
</template>
