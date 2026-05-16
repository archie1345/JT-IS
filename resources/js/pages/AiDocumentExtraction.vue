<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    FileSearch,
    LoaderCircle,
    RotateCcw,
    Save,
    ScanText,
    Upload,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { extractImportantDocumentData } from '@/lib/documentExtraction';
import type {
    ExtractedDocumentItem,
    ExtractedDocumentMetadata,
    ExtractedDocumentPreviewValue,
} from '@/lib/documentExtraction';
import type { BreadcrumbItem } from '@/types';

type ProjectOption = {
    id: number;
    name: string;
};

type ClientOption = {
    id: number;
    name: string;
};

type BackendOcrResponse = {
    engine?: string;
    pages?: {
        angle?: number;
        confidence?: number;
        mode?: string;
        page?: number;
        text?: string;
    }[];
    text?: string;
};

type BudgetPreviewItem = ExtractedDocumentItem & {
    category: string;
    subCategory: string;
};

const props = defineProps<{
    clients?: ClientOption[];
    projects?: ProjectOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'AI Extraction', href: '/ai-document-extraction' },
];

const sourceText = ref('');
const selectedFileName = ref<null | string>(null);
const uploadError = ref<null | string>(null);
const isReadingFile = ref(false);
const readerStatus = ref<null | string>(null);
const ocrProgress = ref(0);
const selectedProjectId = ref('');
const autoCreateProject = ref(false);
const selectedClientId = ref('');
const createdProjects = ref<ProjectOption[]>([]);
const budgetKind = ref<'rab' | 'rap'>('rab');
const saveStatus = ref<null | string>(null);
const saveError = ref<null | string>(null);
const isSavingBudgetItems = ref(false);

const extractedData = computed(() =>
    extractImportantDocumentData(
        sourceText.value,
        selectedFileName.value ?? '',
    ),
);
const hasExtractedText = computed(() => sourceText.value.trim().length > 0);
const hasUploadState = computed(
    () => selectedFileName.value || uploadError.value || hasExtractedText.value,
);
const recommendedGroup = computed(
    () =>
        extractedData.value.preview_groups.find(
            (group) => group.is_recommended,
        ) ?? extractedData.value.preview_groups[0],
);
const projectOptions = computed(() => [
    ...(props.projects ?? []),
    ...createdProjects.value.filter(
        (createdProject) =>
            !(props.projects ?? []).some(
                (project) => project.id === createdProject.id,
            ),
    ),
]);

const metadataLabels: Record<keyof ExtractedDocumentMetadata, string> = {
    doc_number: 'Document Number',
    contract_date: 'Contract Date',
    project_name: 'Project Name',
    contract_value: 'Contract Value',
    location: 'Location',
    owner: 'Owner',
    progress_percent: 'Progress',
    pic_owner: 'PIC Owner',
    pic_pmc: 'PIC PMC',
    pic_contractor: 'PIC Contractor',
};

const metadataRows = computed(() =>
    (Object.keys(metadataLabels) as (keyof ExtractedDocumentMetadata)[]).map(
        (key) => ({
            key,
            label: metadataLabels[key],
            value: extractedData.value.metadata[key],
        }),
    ),
);

const formatMetadataValue = (
    key: keyof ExtractedDocumentMetadata,
    value: ExtractedDocumentMetadata[keyof ExtractedDocumentMetadata],
) => {
    if (value === null || value === '') {
        return 'Not found';
    }

    return key === 'progress_percent' ? `${value}%` : String(value);
};

const formatPreviewValue = (value: ExtractedDocumentPreviewValue) => {
    if (value === null || value === '') {
        return 'Not found';
    }

    if (Array.isArray(value)) {
        return value.length > 0 ? value.join('\n') : 'Not found';
    }

    return String(value);
};

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const budgetItems = computed<BudgetPreviewItem[]>(() =>
    extractedData.value.grouping_results.flatMap((category) =>
        category.sub_categories.flatMap((subCategory) =>
            subCategory.items.map((item) => ({
                ...item,
                category: category.category,
                subCategory: subCategory.name,
            })),
        ),
    ),
);

const budgetItemsTotal = computed(() =>
    budgetItems.value.reduce((total, item) => total + (item.total ?? 0), 0),
);

const projectUpdatePayload = computed(() => {
    const metadata = extractedData.value.metadata;

    return {
        name: metadata.project_name?.slice(0, 200) ?? null,
        contract_number: metadata.doc_number?.slice(0, 100) ?? null,
        contract_value: metadata.contract_value,
        location: metadata.location,
    };
});

const hasProjectUpdates = computed(() =>
    Object.values(projectUpdatePayload.value).some(
        (value) => value !== null && value !== '',
    ),
);
const canSaveExtraction = computed(
    () =>
        (budgetItems.value.length > 0 || hasProjectUpdates.value) &&
        (selectedProjectId.value || autoCreateProject.value),
);

const clearText = () => {
    sourceText.value = '';
    selectedFileName.value = null;
    uploadError.value = null;
    readerStatus.value = null;
    ocrProgress.value = 0;
    saveStatus.value = null;
    saveError.value = null;
};

const csrfToken = () =>
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.content ?? '';

const cookieValue = (name: string) =>
    document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith(`${name}=`))
        ?.split('=')
        .slice(1)
        .join('=');

const buildOcrRequest = (file: File) => {
    const xsrfToken = cookieValue('XSRF-TOKEN');
    const fallbackToken = csrfToken();
    const formData = new FormData();
    formData.append('file', file);

    if (!xsrfToken && fallbackToken) {
        formData.append('_token', fallbackToken);
    }

    return {
        body: formData,
        headers: {
            Accept: 'application/json',
            ...(xsrfToken
                ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfToken) }
                : { 'X-CSRF-TOKEN': fallbackToken }),
        },
    };
};

const postLaravelOcr = (file: File) => {
    const request = buildOcrRequest(file);

    return fetch('/ai-document-extraction/ocr', {
        method: 'POST',
        credentials: 'same-origin',
        headers: request.headers,
        body: request.body,
    });
};

const extractWithLaravelOcr = async (
    file: File,
): Promise<BackendOcrResponse> => {
    let response = await postLaravelOcr(file);

    if (response.status === 419) {
        await fetch('/sanctum/csrf-cookie', {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
            },
        });

        response = await postLaravelOcr(file);
    }

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        const detail =
            typeof payload.detail === 'string' ? payload.detail : null;
        const message =
            typeof payload.message === 'string'
                ? detail
                    ? `${payload.message} ${detail}`
                    : payload.message
                : 'Laravel OCR request failed.';

        throw new Error(message);
    }

    return payload as BackendOcrResponse;
};

const budgetItemPayload = (item: BudgetPreviewItem) => ({
    category: item.category.slice(0, 150),
    sub_category: item.subCategory.slice(0, 150),
    description: item.description.slice(0, 255),
    unit: item.unit?.slice(0, 50) ?? null,
    quantity: item.volume,
    unit_price: item.unit_price,
    total_price: item.total,
});

const saveBudgetItems = async () => {
    saveStatus.value = null;
    saveError.value = null;

    if (!selectedProjectId.value && !autoCreateProject.value) {
        saveError.value =
            'Select a project or enable auto-create project before saving.';
        return;
    }

    if (budgetItems.value.length === 0 && !hasProjectUpdates.value) {
        saveError.value =
            'No project fields or RAB/RAP line items were extracted from this document.';
        return;
    }

    isSavingBudgetItems.value = true;

    try {
        const response = await fetch('/ai-document-extraction/budget-items', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                project_id: selectedProjectId.value
                    ? Number(selectedProjectId.value)
                    : null,
                auto_create_project: autoCreateProject.value,
                client_id: selectedClientId.value
                    ? Number(selectedClientId.value)
                    : null,
                kind: budgetKind.value,
                project_updates: projectUpdatePayload.value,
                items: budgetItems.value.map(budgetItemPayload),
            }),
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(
                typeof payload.message === 'string'
                    ? payload.message
                    : 'Unable to save budget items.',
            );
        }

        const created =
            typeof payload.items_created === 'number'
                ? payload.items_created
                : budgetItems.value.length;
        const projectName =
            typeof payload.project_name === 'string'
                ? ` Project: ${payload.project_name}.`
                : '';
        const action =
            payload.project_created === true
                ? 'Project created.'
                : payload.project_updated === true
                  ? 'Project updated.'
                  : 'Project kept unchanged.';

        if (typeof payload.project_id === 'number') {
            if (typeof payload.project_name === 'string') {
                createdProjects.value = [
                    ...createdProjects.value.filter(
                        (project) => project.id !== payload.project_id,
                    ),
                    {
                        id: payload.project_id,
                        name: payload.project_name,
                    },
                ];
            }

            selectedProjectId.value = String(payload.project_id);
            autoCreateProject.value = false;
        }

        saveStatus.value =
            created > 0
                ? `${action} ${created} ${budgetKind.value.toUpperCase()} item(s) saved.${projectName}`
                : `${action}${projectName}`;
    } catch (error) {
        saveError.value =
            error instanceof Error
                ? error.message
                : 'Unable to save budget items.';
    } finally {
        isSavingBudgetItems.value = false;
    }
};

const readDocumentFile = async (file: File) => {
    selectedFileName.value = file.name;
    uploadError.value = null;
    saveStatus.value = null;
    saveError.value = null;
    isReadingFile.value = true;
    readerStatus.value = 'Uploading to Laravel OCR service';
    ocrProgress.value = 10;

    try {
        const backendResult = await extractWithLaravelOcr(file);

        sourceText.value = backendResult.text ?? '';
        ocrProgress.value = 100;
        readerStatus.value = backendResult.engine
            ? `Extraction complete via ${backendResult.engine}`
            : 'Extraction complete via Laravel OCR service';

        if (!sourceText.value.trim()) {
            uploadError.value =
                'No readable text was returned by the OCR service.';
        }
    } catch (error) {
        sourceText.value = '';
        uploadError.value =
            error instanceof Error
                ? error.message
                : 'Unable to read this document.';
        readerStatus.value = 'Laravel OCR failed';
        ocrProgress.value = 0;
    } finally {
        isReadingFile.value = false;
    }
};

const handleFileInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (file) {
        void readDocumentFile(file);
    }

    input.value = '';
};

const handleDrop = (event: DragEvent) => {
    const file = event.dataTransfer?.files[0];

    if (file) {
        void readDocumentFile(file);
    }
};
</script>

<template>
    <Head title="AI Document Extraction" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <section
                class="border-b border-sidebar-border/70 pb-5 dark:border-sidebar-border"
            >
                <div
                    class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"
                >
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">
                            AI Document Extraction
                        </h1>
                        <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                            Upload a project document, review the extracted
                            fields, then save budget rows when the document
                            contains RAB or RAP details.
                        </p>
                    </div>

                    <div v-if="hasUploadState" class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="clearText"
                        >
                            <RotateCcw class="mr-2 size-4" />
                            Clear
                        </Button>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[24rem_minmax(0,1fr)]">
                <aside class="flex flex-col gap-4">
                    <div
                        class="rounded-lg border border-sidebar-border/70 bg-background p-4 shadow-xs dark:border-sidebar-border"
                    >
                        <div class="flex items-center gap-2">
                            <FileSearch class="size-4 text-muted-foreground" />
                            <h2 class="text-sm font-medium">Document Upload</h2>
                        </div>

                        <label
                            class="mt-4 flex min-h-48 cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-sidebar-border/80 bg-muted/20 px-5 py-8 text-center transition hover:bg-muted/40 dark:border-sidebar-border"
                            @dragover.prevent
                            @drop.prevent="handleDrop"
                        >
                            <input
                                class="sr-only"
                                type="file"
                                accept=".pdf,.txt,.csv,.png,.jpg,.jpeg,.webp,.bmp,.tif,.tiff,text/plain,application/pdf,image/*"
                                @change="handleFileInput"
                            />
                            <span
                                class="flex size-11 items-center justify-center rounded-full bg-background shadow-sm"
                            >
                                <LoaderCircle
                                    v-if="isReadingFile"
                                    class="size-5 animate-spin text-muted-foreground"
                                />
                                <Upload
                                    v-else
                                    class="size-5 text-muted-foreground"
                                />
                            </span>
                            <span class="text-sm font-medium">
                                {{
                                    isReadingFile
                                        ? 'Reading document...'
                                        : 'Upload document'
                                }}
                            </span>
                            <span
                                class="max-w-sm text-xs text-muted-foreground"
                            >
                                PDF, image, text, and CSV files are supported.
                            </span>
                        </label>

                        <div v-if="hasUploadState" class="mt-4 space-y-3">
                            <div
                                v-if="selectedFileName"
                                class="rounded-md border border-sidebar-border/60 px-3 py-2 text-sm dark:border-sidebar-border"
                            >
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >Selected file</span
                                >
                                <span
                                    class="mt-0.5 block font-medium break-words"
                                    >{{ selectedFileName }}</span
                                >
                            </div>

                            <div
                                v-if="readerStatus"
                                class="rounded-md bg-muted/40 px-3 py-2"
                            >
                                <div class="flex items-center gap-2 text-sm">
                                    <ScanText
                                        class="size-4 text-muted-foreground"
                                    />
                                    <span>{{ readerStatus }}</span>
                                </div>
                                <div
                                    v-if="isReadingFile && ocrProgress > 0"
                                    class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted"
                                >
                                    <div
                                        class="h-full rounded-full bg-primary transition-all"
                                        :style="{ width: `${ocrProgress}%` }"
                                    />
                                </div>
                            </div>

                            <p
                                v-if="uploadError"
                                class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
                            >
                                {{ uploadError }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="rounded-lg border border-sidebar-border/70 bg-background p-4 shadow-xs dark:border-sidebar-border"
                    >
                        <h2 class="text-sm font-medium">Extracted Metadata</h2>
                        <dl class="mt-4 grid gap-3">
                            <div
                                class="grid gap-1 rounded-md bg-muted/40 px-3 py-2"
                            >
                                <dt
                                    class="text-xs font-medium text-muted-foreground uppercase"
                                >
                                    Detected Group
                                </dt>
                                <dd class="text-sm font-medium break-words">
                                    {{
                                        extractedData.detected_category ??
                                        'Not found'
                                    }}
                                </dd>
                            </div>
                            <div
                                v-for="row in metadataRows"
                                :key="row.key"
                                class="grid gap-1 rounded-md bg-muted/40 px-3 py-2"
                            >
                                <dt
                                    class="text-xs font-medium text-muted-foreground uppercase"
                                >
                                    {{ row.label }}
                                </dt>
                                <dd class="text-sm font-medium break-words">
                                    {{
                                        formatMetadataValue(row.key, row.value)
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </aside>

                <div class="flex min-w-0 flex-col gap-6">
                    <div
                        class="rounded-lg border border-sidebar-border/70 bg-background p-4 shadow-xs dark:border-sidebar-border"
                    >
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div>
                                <h2 class="text-sm font-medium">
                                    Review Summary
                                </h2>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        recommendedGroup?.description ??
                                        'Upload a document to see extracted fields.'
                                    }}
                                </p>
                            </div>
                            <span
                                v-if="recommendedGroup"
                                class="w-fit rounded-md bg-primary px-2.5 py-1 text-xs font-medium text-primary-foreground"
                            >
                                {{ recommendedGroup.category }}
                            </span>
                        </div>

                        <div
                            class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3"
                        >
                            <div
                                v-for="field in recommendedGroup?.fields ?? []"
                                :key="field.label"
                                class="min-h-20 rounded-md bg-muted/40 px-3 py-2"
                            >
                                <p class="text-xs text-muted-foreground">
                                    {{ field.label }}
                                </p>
                                <p
                                    class="mt-1 text-sm font-medium break-words whitespace-pre-line"
                                >
                                    {{ formatPreviewValue(field.value) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <details
                        v-if="hasExtractedText"
                        class="rounded-lg border border-sidebar-border/70 bg-background dark:border-sidebar-border"
                    >
                        <summary
                            class="cursor-pointer px-4 py-3 text-sm font-medium"
                        >
                            Extracted Text Preview
                        </summary>
                        <pre
                            class="max-h-80 overflow-auto border-t border-sidebar-border/60 p-4 text-xs leading-5 whitespace-pre-wrap dark:border-sidebar-border"
                            >{{ sourceText }}</pre
                        >
                    </details>
                </div>
            </section>

            <section
                class="rounded-lg border border-sidebar-border/70 bg-background shadow-xs dark:border-sidebar-border"
            >
                <div
                    class="flex flex-col gap-4 border-b border-sidebar-border/70 px-4 py-4 lg:flex-row lg:items-center lg:justify-between dark:border-sidebar-border"
                >
                    <div>
                        <h2 class="text-base font-semibold">Budget Items</h2>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                            <span
                                class="rounded-md bg-muted px-2 py-1 text-muted-foreground"
                            >
                                {{ budgetItems.length }} item(s)
                            </span>
                            <span
                                class="rounded-md bg-muted px-2 py-1 font-medium"
                            >
                                {{ formatCurrency(budgetItemsTotal) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-2 lg:min-w-[42rem]">
                        <label class="flex items-center gap-2 text-sm">
                            <input
                                v-model="autoCreateProject"
                                type="checkbox"
                                class="size-4 rounded border-input"
                                :disabled="Boolean(selectedProjectId)"
                            />
                            <span>Auto-create project from document</span>
                        </label>

                        <div
                            class="grid gap-2 sm:grid-cols-[minmax(12rem,1fr)_minmax(10rem,0.8fr)_8rem_auto]"
                        >
                            <select
                                v-model="selectedProjectId"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                @change="autoCreateProject = false"
                            >
                                <option value="">Select project</option>
                                <option
                                    v-for="project in projectOptions"
                                    :key="project.id"
                                    :value="String(project.id)"
                                >
                                    {{ project.name }}
                                </option>
                            </select>

                            <select
                                v-model="selectedClientId"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                :disabled="!autoCreateProject"
                            >
                                <option value="">No client</option>
                                <option
                                    v-for="client in props.clients ?? []"
                                    :key="client.id"
                                    :value="String(client.id)"
                                >
                                    {{ client.name }}
                                </option>
                            </select>

                            <select
                                v-model="budgetKind"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="rab">RAB</option>
                                <option value="rap">RAP</option>
                            </select>

                            <Button
                                type="button"
                                class="whitespace-nowrap"
                                :disabled="
                                    isSavingBudgetItems || !canSaveExtraction
                                "
                                @click="saveBudgetItems"
                            >
                                <LoaderCircle
                                    v-if="isSavingBudgetItems"
                                    class="mr-2 size-4 animate-spin"
                                />
                                <Save v-else class="mr-2 size-4" />
                                Save Extraction
                            </Button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="saveStatus || saveError"
                    class="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
                >
                    <p
                        v-if="saveStatus"
                        class="rounded-md border border-green-500/30 bg-green-500/10 px-3 py-2 text-sm text-green-700 dark:text-green-300"
                    >
                        {{ saveStatus }}
                    </p>
                    <p
                        v-if="saveError"
                        class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
                    >
                        {{ saveError }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead
                            class="border-b border-sidebar-border/70 bg-muted/50 text-left text-xs text-muted-foreground uppercase dark:border-sidebar-border"
                        >
                            <tr>
                                <th class="min-w-[22rem] px-4 py-3 font-medium">
                                    Work Item
                                </th>
                                <th class="min-w-36 px-4 py-3 font-medium">
                                    Section
                                </th>
                                <th
                                    class="w-24 px-4 py-3 text-right font-medium"
                                >
                                    Volume
                                </th>
                                <th class="w-20 px-4 py-3 font-medium">Unit</th>
                                <th
                                    class="w-36 px-4 py-3 text-right font-medium"
                                >
                                    Unit Price
                                </th>
                                <th
                                    class="w-36 px-4 py-3 text-right font-medium"
                                >
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-sidebar-border/60 dark:divide-sidebar-border"
                        >
                            <tr
                                v-for="item in budgetItems"
                                :key="`${item.category}-${item.subCategory}-${item.description}-${item.total ?? 0}`"
                                class="align-top transition hover:bg-muted/30"
                            >
                                <td class="px-4 py-3">
                                    <p
                                        class="font-medium break-words text-foreground"
                                    >
                                        {{ item.description }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <p
                                        class="max-w-48 truncate text-muted-foreground"
                                        :title="`${item.category} / ${item.subCategory}`"
                                    >
                                        {{ item.category }}
                                    </p>
                                    <p
                                        v-if="item.subCategory !== 'Umum'"
                                        class="mt-0.5 max-w-48 truncate text-xs text-muted-foreground"
                                        :title="item.subCategory"
                                    >
                                        {{ item.subCategory }}
                                    </p>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ item.volume ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ item.unit ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{
                                        item.unit_price === null
                                            ? '-'
                                            : formatCurrency(item.unit_price)
                                    }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-medium tabular-nums"
                                >
                                    {{
                                        item.total === null
                                            ? '-'
                                            : formatCurrency(item.total)
                                    }}
                                </td>
                            </tr>
                            <tr v-if="budgetItems.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                                >
                                    Upload a RAB or RAP document to review
                                    extracted line items.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot
                            v-if="budgetItems.length > 0"
                            class="border-t border-sidebar-border/70 bg-muted/30 font-medium dark:border-sidebar-border"
                        >
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-right">
                                    Subtotal
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ formatCurrency(budgetItemsTotal) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
