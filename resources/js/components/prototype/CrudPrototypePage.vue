<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import ProjectDocumentUploadPanel from '@/components/ProjectDocumentUploadPanel.vue';
import ProjectDataTable, {
    type SpreadsheetColumn,
} from '@/components/ProjectDataTable.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import RecordFieldInput from '@/components/prototype/RecordFieldInput.vue';
import type { BreadcrumbItem } from '@/types';
import type { UploadedDocument } from '@/types/project';

type Option = {
    value: number | string;
    label: string;
    hint?: null | string;
};

type FieldType = 'text' | 'number' | 'date' | 'select' | 'textarea';

type Field = {
    name: string;
    label: string;
    type: FieldType;
    placeholder?: string;
    required?: boolean;
    min?: number;
    max?: number;
    step?: string;
    options?: readonly Option[];
};

type Row = Record<string, null | number | string>;
type FormValue = number | string;

const props = withDefaults(
    defineProps<{
        headTitle: string;
        title: string;
        description: string;
        breadcrumbs: BreadcrumbItem[];
        rows: Row[];
        columns: SpreadsheetColumn[];
        fields: readonly Field[];
        createUrl: string;
        updateUrlBase: string;
        deleteUrlBase: string;
        detailUrlBase?: string;
        uploadComponentType?: string;
        uploadProjectId?: number | null;
        uploadedDocuments?: UploadedDocument[];
        projectOptions?: readonly Option[];
        uploadConnectionOptions?: Array<{
            value: string;
            label: string;
            hint?: null | string;
            componentType: string;
            componentId?: null | number;
            projectId?: null | number;
        }>;
        createLabel?: string;
        note?: string;
    }>(),
    {
        createLabel: 'New Record',
        note: '',
        uploadComponentType: '',
        uploadProjectId: null,
        uploadedDocuments: () => [],
        projectOptions: () => [],
        uploadConnectionOptions: () => [],
    },
);

const isOpen = ref(false);
const editingId = ref<null | number>(null);
const deletingId = ref<null | number>(null);

const blankState = computed<Record<string, FormValue>>(() =>
    Object.fromEntries(props.fields.map((field) => [field.name, ''])),
);

const form = useForm<Record<string, FormValue>>({ ...blankState.value });

const resetForm = () => {
    editingId.value = null;
    const payload = { ...blankState.value };
    form.defaults(payload);
    form.reset();
    form.clearErrors();
    Object.assign(form, payload);
};

const openCreate = () => {
    resetForm();
    isOpen.value = true;
};

const openEdit = (row: Row) => {
    editingId.value = Number(row.id);

    const payload = Object.fromEntries(
        props.fields.map((field) => [
            field.name,
            row[field.name] ?? blankState.value[field.name] ?? '',
        ]),
    );

    form.defaults(payload);
    form.reset();
    form.clearErrors();
    Object.assign(form, payload);
    isOpen.value = true;
};

const closeModal = () => {
    isOpen.value = false;
    resetForm();
};

const submit = () => {
    if (editingId.value === null) {
        form.post(props.createUrl, {
            preserveScroll: true,
            onSuccess: closeModal,
        });

        return;
    }

    form.patch(`${props.updateUrlBase}/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: closeModal,
    });
};

const destroyRecord = (row: Row) => {
    const id = Number(row.id);

    if (!window.confirm('Delete this record?')) {
        return;
    }

    deletingId.value = id;

    router.delete(`${props.deleteUrlBase}/${id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
};

const dialogTitle = computed(() =>
    editingId.value === null ? `Create ${props.title}` : `Edit ${props.title}`,
);
</script>

<template>
    <Head :title="props.headTitle" />

    <AppLayout :breadcrumbs="props.breadcrumbs">
        <div
            class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-3 rounded-xl p-2 sm:gap-4 sm:p-4"
        >
            <ProjectDataTable
                :rows="props.rows"
                :columns="props.columns"
                :title="props.title"
                :description="props.description"
                :note="props.note"
                show-create-button
                :create-label="props.createLabel"
                @create="openCreate"
            >
                <template #actions="{ row }">
                    <div class="flex justify-end gap-1">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            @click="openEdit(row as Row)"
                        >
                            <Pencil class="size-4" />
                        </Button>
                        <Button
                            v-if="props.detailUrlBase"
                            variant="ghost"
                            size="icon-sm"
                            @click="
                                router.get(
                                    `${props.detailUrlBase}/${Number((row as Row).id)}`,
                                )
                            "
                        >
                            <ExternalLink class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-destructive"
                            :disabled="deletingId === Number((row as Row).id)"
                            @click="destroyRecord(row as Row)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </template>

                <template
                    v-for="(_, slotName) in $slots"
                    :key="String(slotName)"
                    #[slotName]="slotProps"
                >
                    <slot :name="slotName" v-bind="slotProps" />
                </template>
            </ProjectDataTable>

            <section
                v-if="props.uploadComponentType"
                class="rounded-2xl border border-sidebar-border/70 bg-background/80 p-3 shadow-sm sm:p-5"
            >
                <ProjectDocumentUploadPanel
                    :project-id="props.uploadProjectId"
                    :project-options="props.projectOptions"
                    :component-type="props.uploadComponentType"
                    :connection-options="props.uploadConnectionOptions"
                    :documents="props.uploadedDocuments"
                    title="Page Files"
                    description="Upload supporting files here and they will be linked to the selected project and this page."
                />
            </section>
        </div>

        <Dialog v-model:open="isOpen">
            <DialogContent
                class="max-h-[calc(100vh-2rem)] w-[calc(100vw-2rem)] overflow-y-auto sm:max-w-2xl"
            >
                <DialogHeader>
                    <DialogTitle>{{ dialogTitle }}</DialogTitle>
                </DialogHeader>

                <form
                    class="grid gap-4 py-2 sm:grid-cols-2"
                    @submit.prevent="submit"
                >
                    <RecordFieldInput
                        v-for="field in props.fields"
                        :key="field.name"
                        v-model="form[field.name]"
                        :field="field"
                        :error="form.errors[field.name]"
                    />

                    <DialogFooter class="sm:col-span-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeModal"
                            >Cancel</Button
                        >
                        <Button type="submit" :disabled="form.processing">
                            {{ editingId === null ? 'Save' : 'Update' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
