<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import {
    FileSearch,
    FileText,
    LoaderCircle,
    ScanText,
    Trash2,
    Upload,
} from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { extractImportantDocumentData } from '@/lib/documentExtraction';
import type { UploadedDocument } from '@/types/project';

type ProjectOption = {
    value: number;
    label: string;
    hint?: null | string;
};

type ConnectionOption = {
    value: string;
    label: string;
    hint?: null | string;
    componentType: string;
    componentId?: null | number;
    projectId?: null | number;
};

const props = withDefaults(
    defineProps<{
        projectId?: null | number;
        projectOptions?: ProjectOption[];
        documents?: UploadedDocument[];
        componentType?: string;
        componentId?: null | number;
        connectionOptions?: ConnectionOption[];
        title?: string;
        description?: string;
        emptyText?: string;
    }>(),
    {
        projectId: null,
        projectOptions: () => [],
        documents: () => [],
        componentType: 'project',
        componentId: null,
        connectionOptions: () => [],
        title: 'Document Upload',
        description:
            'Attach files and they will be linked to this project area.',
        emptyText: 'No files uploaded yet.',
    },
);

const input = ref<HTMLInputElement | null>(null);
const isReadingFile = ref(false);
const ocrProgress = ref(0);
const readerStatus = ref<null | string>(null);
const uploadError = ref<null | string>(null);
const applyStatus = ref<null | string>(null);
const applyError = ref<null | string>(null);
const isApplying = ref(false);
const ocrText = ref('');
const ocrEngine = ref<null | string>(null);
const selectedProjectId = ref<null | number>(
    props.projectId ?? props.projectOptions[0]?.value ?? null,
);
const selectedConnectionValue = ref(
    `${props.componentType}:${props.componentId ?? 'general'}`,
);

watch(
    () => props.projectId,
    (projectId) => {
        if (projectId) {
            selectedProjectId.value = projectId;
        }
    },
);

const form = useForm<{
    documents: File[];
    component_type: string;
    component_id: null | number;
    document_type: string;
    ocr_text: string;
    ocr_engine: string;
}>({
    documents: [],
    component_type: props.componentType,
    component_id: props.componentId,
    document_type: props.componentType,
    ocr_text: '',
    ocr_engine: '',
});

const canChooseProject = computed(
    () => !props.projectId && props.projectOptions.length > 0,
);
const canChooseConnection = computed(() => props.connectionOptions.length > 0);
const defaultConnectionOption = computed<ConnectionOption>(() => ({
    value: `${props.componentType}:${props.componentId ?? 'general'}`,
    label: 'General page',
    hint: 'Do not attach to a specific row',
    componentType: props.componentType,
    componentId: props.componentId,
    projectId: props.projectId,
}));
const connectionSelectOptions = computed(() => {
    const hasDefault = props.connectionOptions.some(
        (option) => option.value === defaultConnectionOption.value.value,
    );

    return hasDefault
        ? props.connectionOptions
        : [defaultConnectionOption.value, ...props.connectionOptions];
});
const selectedConnection = computed(
    () =>
        connectionSelectOptions.value.find(
            (option) => option.value === selectedConnectionValue.value,
        ) ?? null,
);
const effectiveComponentType = computed(
    () => selectedConnection.value?.componentType ?? props.componentType,
);
const effectiveComponentId = computed(
    () => selectedConnection.value?.componentId ?? props.componentId,
);
const uploadUrl = computed(() =>
    selectedProjectId.value
        ? `/projects/${selectedProjectId.value}/documents`
        : '',
);

watch(selectedConnection, (connection) => {
    if (connection?.projectId) {
        selectedProjectId.value = connection.projectId;
    }
});
const selectedFileNames = computed(() =>
    form.documents.map((file) => file.name).join(', '),
);
const statusText = computed(() => {
    if (isReadingFile.value) return 'Reading document...';
    if (form.processing) return 'Uploading document...';
    if (form.documents.length > 0) {
        return `${form.documents.length} file(s) ready to upload`;
    }

    return 'Upload document';
});
const hasOcrState = computed(() =>
    Boolean(readerStatus.value || uploadError.value || ocrText.value),
);
const ocrPreview = computed(() => ocrText.value.trim().slice(0, 600));
const truncateText = (value: null | string | undefined, max: number) => {
    const cleaned = value?.trim();

    return cleaned ? cleaned.slice(0, max) : null;
};
const extractedData = computed(() =>
    ocrText.value.trim()
        ? extractImportantDocumentData(
              ocrText.value,
              effectiveComponentType.value,
          )
        : null,
);
const projectUpdates = computed(() => {
    const metadata = extractedData.value?.metadata;

    if (!metadata) return {};

    return {
        name: truncateText(metadata.project_name, 200),
        contract_number: truncateText(metadata.doc_number, 100),
        contract_value: metadata.contract_value,
        location: truncateText(metadata.location, 1000),
    };
});
const budgetItems = computed(() =>
    (extractedData.value?.grouping_results ?? []).flatMap((category) =>
        category.sub_categories.flatMap((subCategory) =>
            subCategory.items.map((item) => ({
                category: category.category.slice(0, 150),
                sub_category: subCategory.name.slice(0, 150),
                description: item.description.slice(0, 255),
                unit: item.unit?.slice(0, 50) ?? null,
                quantity: item.volume,
                unit_price: item.unit_price,
                total_price: item.total,
            })),
        ),
    ),
);
const extractedAmount = computed(
    () =>
        extractedData.value?.metadata.contract_value ??
        budgetItems.value.reduce(
            (sum, item) => sum + Number(item.total_price ?? 0),
            0,
        ) ??
        null,
);
const applySummary = computed(() => {
    const updates = Object.values(projectUpdates.value).filter(
        (value) => value !== null && value !== undefined && value !== '',
    ).length;
    const itemCount = budgetItems.value.length;

    if (!extractedData.value) return 'No extracted data yet.';

    return `${updates} project field(s), ${itemCount} budget row(s) detected.`;
});
const canApplyExtraction = computed(
    () =>
        Boolean(selectedProjectId.value && ocrText.value.trim()) &&
        (effectiveComponentType.value === 'project' ||
            effectiveComponentType.value === 'rab' ||
            effectiveComponentType.value === 'rap' ||
            effectiveComponentType.value === 'invoice' ||
            effectiveComponentType.value === 'project_cost' ||
            effectiveComponentType.value === 'progress_report'),
);

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

const runOcr = async (file: File) => {
    uploadError.value = null;
    readerStatus.value = 'Uploading to Laravel OCR service';
    ocrProgress.value = 10;
    isReadingFile.value = true;
    ocrText.value = '';
    ocrEngine.value = null;
    applyStatus.value = null;
    applyError.value = null;

    try {
        const request = buildOcrRequest(file);
        let response = await fetch('/projects/documents/ocr', {
            method: 'POST',
            credentials: 'same-origin',
            headers: request.headers,
            body: request.body,
        });

        if (response.status === 419) {
            await fetch('/sanctum/csrf-cookie', {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            const retryRequest = buildOcrRequest(file);
            response = await fetch('/projects/documents/ocr', {
                method: 'POST',
                credentials: 'same-origin',
                headers: retryRequest.headers,
                body: retryRequest.body,
            });
        }

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const detail =
                typeof payload.detail === 'string' ? payload.detail : null;
            throw new Error(
                typeof payload.message === 'string'
                    ? detail
                        ? `${payload.message} ${detail}`
                        : payload.message
                    : 'OCR request failed.',
            );
        }

        ocrText.value = typeof payload.text === 'string' ? payload.text : '';
        ocrEngine.value =
            typeof payload.engine === 'string' ? payload.engine : 'ocr';
        readerStatus.value = ocrEngine.value
            ? `Extraction complete via ${ocrEngine.value}`
            : 'Extraction complete';
        ocrProgress.value = 100;

        if (!ocrText.value.trim()) {
            uploadError.value = 'No readable text was returned by OCR.';
        }
    } catch (error) {
        uploadError.value =
            error instanceof Error
                ? error.message
                : 'Unable to OCR this document.';
        readerStatus.value = 'OCR failed';
        ocrProgress.value = 0;
    } finally {
        isReadingFile.value = false;
    }
};

const applyExtraction = async () => {
    if (!selectedProjectId.value || !ocrText.value.trim()) {
        return;
    }

    applyStatus.value = null;
    applyError.value = null;
    isApplying.value = true;

    try {
        const response = await fetch('/projects/documents/apply-extraction', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                project_id: selectedProjectId.value,
                component_type: effectiveComponentType.value,
                component_id: effectiveComponentId.value,
                project_updates: projectUpdates.value,
                items:
                    effectiveComponentType.value === 'rab' ||
                    effectiveComponentType.value === 'rap'
                        ? budgetItems.value
                        : [],
                progress_percent:
                    effectiveComponentType.value === 'progress_report'
                        ? extractedData.value?.metadata.progress_percent
                        : null,
                amount:
                    effectiveComponentType.value === 'invoice' ||
                    effectiveComponentType.value === 'project_cost'
                        ? extractedAmount.value
                        : null,
            }),
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(
                typeof payload.message === 'string'
                    ? payload.message
                    : 'Unable to apply extracted data.',
            );
        }

        applyStatus.value =
            typeof payload.message === 'string'
                ? payload.message
                : 'Extracted data applied.';
        router.reload({ preserveScroll: true });
    } catch (error) {
        applyError.value =
            error instanceof Error
                ? error.message
                : 'Unable to apply extracted data.';
    } finally {
        isApplying.value = false;
    }
};

const setFiles = (files: File[]) => {
    form.documents = files;
    ocrText.value = '';
    ocrEngine.value = null;
    uploadError.value = null;
    readerStatus.value = null;
    ocrProgress.value = 0;

    if (files[0]) {
        void runOcr(files[0]);
    }
};

const handleChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    setFiles(Array.from(target.files ?? []));
};

const handleDrop = (event: DragEvent) => {
    setFiles(Array.from(event.dataTransfer?.files ?? []));
};

const upload = () => {
    if (!uploadUrl.value || form.documents.length === 0) {
        return;
    }

    form.component_type = effectiveComponentType.value;
    form.component_id = effectiveComponentId.value;
    form.document_type = effectiveComponentType.value;
    form.ocr_text = form.documents.length === 1 ? ocrText.value : '';
    form.ocr_engine =
        form.documents.length === 1 && ocrEngine.value ? ocrEngine.value : '';

    form.post(uploadUrl.value, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('documents');
            form.ocr_text = '';
            form.ocr_engine = '';
            ocrText.value = '';
            ocrEngine.value = null;
            readerStatus.value = null;
            uploadError.value = null;
            ocrProgress.value = 0;
            if (input.value) {
                input.value.value = '';
            }
        },
    });
};

const removeDocument = (document: UploadedDocument) => {
    if (!window.confirm('Delete this uploaded file?')) {
        return;
    }

    router.delete(`/projects/documents/${document.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-4">
        <div
            class="rounded-lg border border-sidebar-border/70 bg-background p-4 shadow-xs dark:border-sidebar-border"
        >
            <div class="flex items-center gap-2">
                <FileSearch class="size-4 text-muted-foreground" />
                <h3 class="text-sm font-medium">{{ props.title }}</h3>
            </div>

            <label v-if="canChooseProject" class="mt-4 block space-y-1.5">
                <span class="text-xs font-medium text-muted-foreground"
                    >Project</span
                >
                <select
                    v-model.number="selectedProjectId"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option
                        v-for="project in props.projectOptions"
                        :key="project.value"
                        :value="project.value"
                    >
                        {{ project.label }}
                    </option>
                </select>
            </label>

            <label v-if="canChooseConnection" class="mt-4 block space-y-1.5">
                <span class="text-xs font-medium text-muted-foreground"
                    >Connect to</span
                >
                <select
                    v-model="selectedConnectionValue"
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                    <option
                        v-for="connection in connectionSelectOptions"
                        :key="connection.value"
                        :value="connection.value"
                    >
                        {{
                            connection.hint
                                ? `${connection.label} - ${connection.hint}`
                                : connection.label
                        }}
                    </option>
                </select>
            </label>

            <label
                class="mt-4 flex min-h-48 cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-sidebar-border/80 bg-muted/20 px-5 py-8 text-center transition hover:bg-muted/40 dark:border-sidebar-border"
                @dragover.prevent
                @drop.prevent="handleDrop"
            >
                <input
                    ref="input"
                    type="file"
                    multiple
                    class="sr-only"
                    @change="handleChange"
                />
                <span
                    class="flex size-11 items-center justify-center rounded-full bg-background shadow-sm"
                >
                    <LoaderCircle
                        v-if="form.processing || isReadingFile"
                        class="size-5 animate-spin text-muted-foreground"
                    />
                    <Upload v-else class="size-5 text-muted-foreground" />
                </span>
                <span class="text-sm font-medium">{{ statusText }}</span>
                <span class="max-w-sm text-xs text-muted-foreground">
                    {{ props.description }}
                </span>
            </label>

            <div
                v-if="
                    form.documents.length > 0 ||
                    form.processing ||
                    form.errors.documents ||
                    hasOcrState
                "
                class="mt-4 space-y-3"
            >
                <div
                    v-if="selectedFileNames"
                    class="rounded-md border border-sidebar-border/60 px-3 py-2 text-sm dark:border-sidebar-border"
                >
                    <span class="block text-xs text-muted-foreground"
                        >Selected file</span
                    >
                    <span class="mt-0.5 block font-medium break-words">
                        {{ selectedFileNames }}
                    </span>
                </div>

                <div
                    v-if="form.processing || isReadingFile || readerStatus"
                    class="rounded-md bg-muted/40 px-3 py-2"
                >
                    <div class="flex items-center gap-2 text-sm">
                        <LoaderCircle
                            v-if="form.processing || isReadingFile"
                            class="size-4 animate-spin text-muted-foreground"
                        />
                        <ScanText v-else class="size-4 text-muted-foreground" />
                        <span>{{
                            form.processing
                                ? 'Saving upload to this project...'
                                : (readerStatus ?? 'OCR ready')
                        }}</span>
                    </div>
                    <div
                        v-if="
                            form.processing || isReadingFile || ocrProgress > 0
                        "
                        class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-all"
                            :style="{
                                width: `${form.processing ? 70 : ocrProgress}%`,
                            }"
                        />
                    </div>
                </div>

                <p
                    v-if="uploadError"
                    class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
                >
                    {{ uploadError }}
                </p>

                <div
                    v-if="ocrPreview"
                    class="rounded-md border border-sidebar-border/60 px-3 py-2 text-sm dark:border-sidebar-border"
                >
                    <span class="block text-xs text-muted-foreground"
                        >OCR preview</span
                    >
                    <p
                        class="mt-1 max-h-32 overflow-y-auto text-xs leading-relaxed whitespace-pre-wrap text-muted-foreground"
                    >
                        {{ ocrPreview }}
                    </p>
                </div>

                <div
                    v-if="ocrText"
                    class="rounded-md border border-sidebar-border/60 px-3 py-2 text-sm dark:border-sidebar-border"
                >
                    <span class="block text-xs text-muted-foreground"
                        >Detected fields</span
                    >
                    <p class="mt-0.5 text-sm">{{ applySummary }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            size="sm"
                            :disabled="!canApplyExtraction || isApplying"
                            @click="applyExtraction"
                        >
                            <LoaderCircle
                                v-if="isApplying"
                                class="mr-2 size-4 animate-spin"
                            />
                            <ScanText v-else class="mr-2 size-4" />
                            Apply to selected fields
                        </Button>
                        <span
                            v-if="applyStatus"
                            class="text-xs text-emerald-600"
                            >{{ applyStatus }}</span
                        >
                    </div>
                    <p
                        v-if="applyError"
                        class="mt-2 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
                    >
                        {{ applyError }}
                    </p>
                </div>

                <InputError :message="form.errors.documents" />
            </div>

            <div class="mt-4 flex justify-end">
                <Button
                    type="button"
                    :disabled="
                        form.processing ||
                        isReadingFile ||
                        form.documents.length === 0 ||
                        !uploadUrl
                    "
                    @click="upload"
                >
                    <Upload class="mr-2 size-4" />
                    Upload
                </Button>
            </div>
        </div>

        <div
            class="rounded-lg border border-sidebar-border/70 bg-background p-4 shadow-xs dark:border-sidebar-border"
        >
            <div class="flex items-center gap-2">
                <FileText class="size-4 text-muted-foreground" />
                <h3 class="text-sm font-medium">Uploaded Documents</h3>
            </div>

            <div class="mt-4 grid gap-2">
                <div
                    v-for="document in props.documents"
                    :key="document.id"
                    class="flex flex-col gap-3 rounded-md border border-sidebar-border/60 px-3 py-2 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border"
                >
                    <a
                        :href="document.url"
                        target="_blank"
                        rel="noreferrer"
                        class="flex min-w-0 items-start gap-3"
                    >
                        <FileText
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="min-w-0">
                            <span
                                class="block truncate text-sm font-medium text-foreground"
                            >
                                {{ document.originalName }}
                            </span>
                            <span class="block text-xs text-muted-foreground">
                                {{
                                    document.projectName
                                        ? `${document.projectName} / `
                                        : ''
                                }}
                                {{ document.createdAt ?? 'uploaded' }}
                                /
                                {{
                                    document.size
                                        ? Math.round(document.size / 1024) +
                                          ' KB'
                                        : 'file'
                                }}
                                <template v-if="document.ocrProcessedAt">
                                    / OCR
                                    {{ document.ocrEngine ?? 'processed' }}
                                </template>
                            </span>
                        </span>
                    </a>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        class="self-end text-destructive sm:self-auto"
                        @click="removeDocument(document)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>

                <div
                    v-if="props.documents.length === 0"
                    class="rounded-md border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground"
                >
                    {{ props.emptyText }}
                </div>
            </div>
        </div>
    </div>
</template>
