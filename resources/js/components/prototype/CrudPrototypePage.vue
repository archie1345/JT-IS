<script setup lang="ts">
import { computed, ref, useSlots } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ExternalLink, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import DocumentUploadPanel from '@/components/shared/DocumentUploadPanel.vue';
import DataTable, {
    type SpreadsheetColumn,
} from '@/components/shared/DataTable.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import OptionSelect from '@/components/prototype/OptionSelect.vue';
import RecordFieldInput from '@/components/prototype/RecordFieldInput.vue';
import type { BreadcrumbItem } from '@/types';
import type { UploadedDocument } from '@/types/project';

type Option = {
    value: number | string;
    label: string;
    hint?: null | string;
};

type ProjectOption = {
    value: number;
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
type Pagination = {
    currentPage: number;
    lastPage: number;
    maxPerPage?: number;
    perPage: number;
    perPageOptions?: number[];
    total: number;
};

type OcrConfig = {
    configured?: boolean;
    unavailableMessage?: string;
};

type SharedOcrProps = {
    features?: {
        ocr?: OcrConfig;
    };
};

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
        projectOptions?: readonly ProjectOption[];
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
        pagination?: null | Pagination;
        ocrConfigured?: boolean;
        ocrUnavailableMessage?: string;
    }>(),
    {
        createLabel: 'New Record',
        note: '',
        uploadComponentType: '',
        uploadProjectId: null,
        uploadedDocuments: () => [],
        projectOptions: () => [],
        uploadConnectionOptions: () => [],
        pagination: null,
        ocrConfigured: true,
        ocrUnavailableMessage: 'OCR belum aktif. Dokumen tetap bisa diunggah, lanjutkan input manual.',
    },
);

const page = usePage<SharedOcrProps>();
const ocrConfig = computed<OcrConfig>(() => ({
    configured: props.ocrConfigured ?? page.props.features?.ocr?.configured ?? true,
    unavailableMessage: props.ocrUnavailableMessage ?? page.props.features?.ocr?.unavailableMessage,
}));

const isOpen = ref(false);
const editingId = ref<null | number>(null);
const deletingId = ref<null | number>(null);
const slots = useSlots();

/**
 * OCR-related state for document upload management
 */
const ocrState = ref<{
    isProcessing: boolean;
    hasError: boolean;
    errorMessage?: string;
    processedDocumentCount: number;
}>({
    isProcessing: false,
    hasError: false,
    processedDocumentCount: 0,
});

/**
 * Check if OCR is available for document uploads
 */
const isOcrAvailable = computed(
    () => props.uploadComponentType && ocrConfig.value.configured,
);

/**
 * Get OCR configuration message
 */
const ocrMessage = computed(() => {
    if (ocrConfig.value.unavailableMessage && !ocrConfig.value.configured) {
        return ocrConfig.value.unavailableMessage;
    }
    return null;
});

/**
 * Check if create button should be shown
 * Hide for RAB and RAP components
 */
const shouldShowCreateButton = computed(() => {
    return props.uploadComponentType !== 'rab' && props.uploadComponentType !== 'rap';
});

const blankState = computed<Record<string, FormValue>>(() =>
    Object.fromEntries(props.fields.map((field) => [field.name, ''])),
);

/**
 * Helper function to handle OCR processing completion
 */
const handleOcrProcessed = (data: {
    documentId: number;
    ocrText: string;
    ocrEngine: string;
}): void => {
    ocrState.value.processedDocumentCount += 1;
};

/**
 * Helper function to handle OCR errors
 */
const handleOcrError = (error: string): void => {
    ocrState.value.hasError = true;
    ocrState.value.errorMessage = error;
};

/**
 * Reset OCR state after document operations
 */
const resetOcrState = (): void => {
    ocrState.value = {
        isProcessing: false,
        hasError: false,
        processedDocumentCount: 0,
    };
};

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
    resetOcrState();
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

const forwardedSlots = computed(() =>
    Object.keys(slots).filter((slotName) => slotName !== 'toolbar-actions'),
);

const rowsPerPageOptions = computed(
    () => props.pagination?.perPageOptions ?? [10, 15, 25, 50, 100],
);

const rowsPerPageSelectOptions = computed(() =>
    rowsPerPageOptions.value.map((option) => ({
        value: String(option),
        label: String(option),
    })),
);

const rowsPerPageValue = computed({
    get: () =>
        String(props.pagination?.perPage ?? rowsPerPageOptions.value[0] ?? 15),
    set: (value: string) => {
        changeRowsPerPage(value);
    },
});

const changeRowsPerPage = (value: string) => {
    const params = new URLSearchParams(window.location.search);

    params.set('per_page', value);
    params.delete('page');

    router.get(window.location.pathname, Object.fromEntries(params.entries()), {
        preserveScroll: true,
    });
};

const goToPage = (page: number) => {
    if (!props.pagination) {
        return;
    }

    const targetPage = Math.min(Math.max(page, 1), props.pagination.lastPage);
    const params = new URLSearchParams(window.location.search);

    params.set('page', String(targetPage));
    params.set('per_page', String(props.pagination.perPage));

    router.get(window.location.pathname, Object.fromEntries(params.entries()), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="props.headTitle" />

    <AppLayout :breadcrumbs="props.breadcrumbs">
        <div
            class="flex min-h-[calc(100vh-8rem)] min-w-0 flex-1 flex-col gap-3 rounded-xl p-2 sm:gap-4 sm:p-4"
        >
            <DataTable
                :rows="props.rows"
                :columns="props.columns"
                :title="props.title"
                :description="props.description"
                :note="props.note"
                :show-create-button="shouldShowCreateButton"
                :create-label="props.createLabel"
                @create="openCreate"
            >
                <template #toolbar-actions>
                    <slot name="toolbar-actions" />
                </template>

                <template #actions="{ row }">
                    <div class="flex justify-end gap-1">
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
                        <slot name="row-extra-actions" :row="row" />
                    </div>
                </template>

                <template
                    v-for="slotName in forwardedSlots"
                    :key="slotName"
                    #[slotName]="slotProps"
                >
                    <slot :name="slotName" v-bind="slotProps" />
                </template>
            </DataTable>

            <div
                v-if="props.pagination"
                class="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 bg-background/80 px-3 py-2 text-xs shadow-sm sm:flex-row sm:items-center sm:justify-between sm:text-sm"
            >
                <div
                    class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-2"
                >
                    <div class="flex items-center gap-2">
                        <span class="whitespace-nowrap text-muted-foreground">
                            Show
                        </span>
                        <div class="w-20 sm:w-24">
                            <OptionSelect
                                v-model="rowsPerPageValue"
                                :options="rowsPerPageSelectOptions"
                                trigger-id="rows_per_page"
                                placeholder="Rows"
                            />
                        </div>
                        <span class="whitespace-nowrap text-muted-foreground">
                            rows
                        </span>
                    </div>

                    <span class="text-muted-foreground">
                        Page {{ props.pagination.currentPage }} of
                        {{ props.pagination.lastPage }} -
                        {{ props.pagination.total }} total entries
                    </span>
                </div>

                <div class="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="props.pagination.currentPage <= 1"
                        @click="goToPage(props.pagination.currentPage - 1)"
                    >
                        Previous
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="
                            props.pagination.currentPage >=
                            props.pagination.lastPage
                        "
                        @click="goToPage(props.pagination.currentPage + 1)"
                    >
                        Next
                    </Button>
                </div>
            </div>

            <section
                v-if="props.uploadComponentType"
                class="min-w-0 overflow-hidden rounded-xl border border-sidebar-border/70 bg-background/80 p-3 shadow-sm sm:rounded-2xl sm:p-5"
            >
                <DocumentUploadPanel
                    :project-id="props.uploadProjectId"
                    :project-options="props.projectOptions"
                    :component-type="props.uploadComponentType"
                    :connection-options="props.uploadConnectionOptions"
                    :documents="props.uploadedDocuments"
                    title="Supporting Documents"
                    description="Upload supporting files and link them to the selected project and monitoring record."
                />
            </section>
        </div>

        <Dialog v-model:open="isOpen">
            <DialogContent
                class="flex max-h-[calc(100dvh-2rem)] w-[calc(100vw-2rem)] !max-w-2xl flex-col overflow-hidden p-4 sm:p-6"
            >
                <DialogHeader class="shrink-0">
                    <DialogTitle>{{ dialogTitle }}</DialogTitle>
                </DialogHeader>

                <form
                    class="grid min-h-0 min-w-0 flex-1 gap-4 overflow-x-hidden overflow-y-auto py-2 pr-1 sm:grid-cols-2"
                    @submit.prevent="submit"
                >
                    <RecordFieldInput
                        v-for="field in props.fields"
                        :key="field.name"
                        v-model="form[field.name]"
                        :field="field"
                        :error="form.errors[field.name]"
                    />

                    <DialogFooter class="shrink-0 sm:col-span-2">
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
