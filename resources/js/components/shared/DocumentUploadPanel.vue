<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import {
    FileSearch,
    FileText,
    LoaderCircle,
    RotateCcw,
    ScanText,
    Trash2,
    Upload,
} from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import TextPreview from '@/components/shared/TextPreview.vue';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import OptionSelect from '@/components/prototype/OptionSelect.vue';
import { useDocumentOcr } from '@/composables/useDocumentOcr';
import { extractImportantDocumentData } from '@/lib/documentExtraction';
import { csrfFetch, type OcrResponse } from '@/lib/ocr';
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

type SharedOcrProps = {
    features?: {
        ocr?: {
            configured?: boolean;
            unavailableMessage?: string;
        };
    };
};

const props = withDefaults(
    defineProps<{
        projectId?: null | number;
        projectOptions?: readonly ProjectOption[];
        documents?: UploadedDocument[];
        componentType?: string;
        componentId?: null | number;
        connectionOptions?: readonly ConnectionOption[];
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
        title: 'Unggah Dokumen',
        description: 'Lampirkan dokumen dan hubungkan ke area proyek terkait.',
        emptyText: 'Belum ada dokumen.',
    },
);

const page = usePage<SharedOcrProps>();
const input = ref<HTMLInputElement | null>(null);
const applyStatus = ref<null | string>(null);
const applyError = ref<null | string>(null);
const ocrNotice = ref<null | string>(null);
const appliedExtractionComponent = ref<null | { id: number; type: string }>(
    null,
);
const storedOcrDocumentId = ref<null | number>(null);
const isApplying = ref(false);
const isPreviewOpen = ref(false);
const {
    engine: ocrEngine,
    error: uploadError,
    extractFile: extractOcrFile,
    hasState: hasOcrState,
    isReading: isReadingFile,
    preview: ocrPreview,
    progress: ocrProgress,
    reset: resetOcr,
    status: readerStatus,
    text: ocrText,
} = useDocumentOcr({
    initialStatus: 'Mengirim dokumen ke OCR',
    successStatus: 'OCR selesai',
    failedStatus: 'OCR gagal',
    emptyTextMessage:
        'OCR selesai, tetapi tidak menemukan teks yang bisa dibaca. Silakan input manual.',
});
const previewProjectUpdates = ref<Record<string, number | string>>({});
const previewProgressPercent = ref<number | string>('');
const previewAmount = ref<number | string>('');
const previewBudgetItems = ref<
    {
        category: string;
        sub_category: string;
        description: string;
        unit: null | string;
        quantity: null | number | string;
        unit_price: null | number | string;
        total_price: null | number | string;
    }[]
>([]);
const selectedProjectId = ref<null | number>(
    props.projectId ?? props.projectOptions[0]?.value ?? null,
);
const selectedConnectionValue = ref(
    `${props.componentType}:${props.componentId ?? 'general'}`,
);
const selectedProjectValue = computed({
    get: () => (selectedProjectId.value ? String(selectedProjectId.value) : ''),
    set: (value: string) => {
        selectedProjectId.value = value ? Number(value) : null;
    },
});

const documentTypeOptions = [
    { value: 'contract', label: 'Kontrak' },
    { value: 'rab', label: 'RAB' },
    { value: 'rap', label: 'rap' },
    { value: 'bamc', label: 'BAMC / Berita Acara' },
    { value: 'invoice', label: 'Tagihan' },
    { value: 'receipt', label: 'Bukti Pembayaran' },
    { value: 'other', label: 'Dokumen Lainnya' },
];

const defaultDocumentTypeFor = (componentType: string) =>
    ({
        rab: 'rab',
        rap: 'rap',
        invoice: 'invoice',
        project_cost: 'receipt',
        progress_report: 'bamc',
        project: 'contract',
    })[componentType] ?? 'other';

const initialDocumentTypeFor = (componentType: string) =>
    componentType === 'project' ? '' : defaultDocumentTypeFor(componentType);

const extractionTypeForDocumentType: Record<string, string> = {
    rab: 'rab',
    rap: 'rap',
    invoice: 'invoice',
    receipt: 'project_cost',
    bamc: 'progress_report',
};

const documentTypeLabel = (value?: null | string) =>
    documentTypeOptions.find((option) => option.value === value)?.label ??
    value ??
    'Dokumen';

const ocrStatusLabel = (document: UploadedDocument) =>
    document.ocrProcessedAt
        ? `OCR diproses${document.ocrEngine ? ` (${document.ocrEngine})` : ''}`
        : 'OCR belum diproses';

const documentSummary = (document: UploadedDocument) =>
    [
        document.projectName,
        document.createdAt ?? 'diunggah',
        documentTypeLabel(document.documentType),
        document.size ? `${Math.round(document.size / 1024)} KB` : 'file',
        ocrStatusLabel(document),
    ]
        .filter(Boolean)
        .join(' / ');

const sanitizePercentInput = (value: string) => {
    const cleaned = value
        .replace(',', '.')
        .replace(/[^\d.]/g, '')
        .replace(/(\..*)\./g, '$1');
    const [whole = '', decimal] = cleaned.split('.');
    const cappedWhole =
        whole === '' ? '' : String(Math.min(Number(whole), 100));
    const cappedDecimal =
        decimal === undefined ? '' : `.${decimal.slice(0, 2)}`;
    const nextValue = `${cappedWhole}${cappedDecimal}`;

    return nextValue !== '' && Number(nextValue) > 100 ? '100' : nextValue;
};

const handleProgressPercentInput = (event: Event) => {
    if (!(event.target instanceof HTMLInputElement)) {
        return;
    }

    const sanitizedValue = sanitizePercentInput(event.target.value);
    event.target.value = sanitizedValue;
    previewProgressPercent.value = sanitizedValue;
};

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
    document_type: initialDocumentTypeFor(props.componentType),
    ocr_text: '',
    ocr_engine: '',
});

const canChooseProject = computed(
    () => !props.projectId && props.projectOptions.length > 0,
);
const defaultConnectionOption = computed<ConnectionOption>(() => ({
    value: `${props.componentType}:${props.componentId ?? 'general'}`,
    label: 'Dokumen umum',
    hint: 'Tidak terhubung ke baris tertentu',
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
const extractionComponentType = computed(() => {
    if (effectiveComponentType.value !== 'project') {
        return effectiveComponentType.value;
    }

    return extractionTypeForDocumentType[form.document_type] ?? 'project';
});
const extractionComponentId = computed(() => {
    if (extractionComponentType.value === effectiveComponentType.value) {
        return effectiveComponentId.value;
    }

    if (
        appliedExtractionComponent.value?.type ===
        extractionComponentType.value
    ) {
        return appliedExtractionComponent.value.id;
    }

    return null;
});
const uploadUrl = computed(() =>
    selectedProjectId.value
        ? `/projects/${selectedProjectId.value}/documents`
        : '',
);
const hasDocumentType = computed(() => form.document_type.trim() !== '');
const ocrConfigured = computed(
    () => page.props.features?.ocr?.configured ?? true,
);
const ocrUnavailableMessage = computed(
    () =>
        page.props.features?.ocr?.unavailableMessage ??
        'OCR belum aktif. Dokumen tetap bisa diunggah, lanjutkan input manual.',
);

watch(selectedConnection, (connection) => {
    appliedExtractionComponent.value = null;

    if (connection?.projectId) {
        selectedProjectId.value = connection.projectId;
    }

    if (connection?.componentType) {
        form.document_type = initialDocumentTypeFor(connection.componentType);
    }
});
watch(
    () => form.document_type,
    () => {
        appliedExtractionComponent.value = null;
        storedOcrDocumentId.value = null;
    },
);
const selectedFileNames = computed(() =>
    form.documents.map((file) => file.name).join(', '),
);
const statusText = computed(() => {
    if (!hasDocumentType.value) return 'Pilih jenis dokumen';
    if (isReadingFile.value) return 'Membaca dokumen...';
    if (form.processing) return 'Mengunggah dokumen...';
    if (form.documents.length > 0) {
        return `${form.documents.length} file siap diunggah`;
    }

    return 'Unggah dokumen';
});
const truncateText = (value: null | string | undefined, max: number) => {
    const cleaned = value?.trim();

    return cleaned ? cleaned.slice(0, max) : null;
};
const extractedData = computed(() =>
    ocrText.value.trim()
        ? extractImportantDocumentData(
              ocrText.value,
              extractionComponentType.value,
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
const projectUpdateLabels: Record<string, string> = {
    name: 'Nama Proyek',
    contract_number: 'Nomor Kontrak',
    contract_value: 'Nilai Kontrak',
    location: 'Lokasi',
};
const projectUpdatePreview = computed(() =>
    Object.entries(previewProjectUpdates.value)
        .filter(
            ([, value]) =>
                value !== null && value !== undefined && value !== '',
        )
        .map(([key, value]) => ({
            key,
            label: projectUpdateLabels[key] ?? key,
            value,
        })),
);
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
const metadataPreview = computed(() => {
    const rows = [
        {
            label: 'Progress',
            value:
                extractionComponentType.value === 'progress_report' &&
                previewProgressPercent.value !== ''
                    ? previewProgressPercent.value
                    : null,
        },
        {
            label: 'Nilai',
            value:
                (extractionComponentType.value === 'invoice' ||
                    extractionComponentType.value === 'project_cost') &&
                previewAmount.value !== ''
                    ? previewAmount.value
                    : null,
        },
    ];

    return rows.filter((row) => row.value !== null && row.value !== '');
});
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

    if (!extractedData.value) return 'Belum ada draft hasil OCR.';

    return `${updates} field proyek dan ${itemCount} baris anggaran terdeteksi.`;
});
const canApplyExtraction = computed(
    () =>
        Boolean(selectedProjectId.value && ocrText.value.trim()) &&
        (extractionComponentType.value === 'project' ||
            extractionComponentType.value === 'rab' ||
            extractionComponentType.value === 'rap' ||
            extractionComponentType.value === 'invoice' ||
            extractionComponentType.value === 'project_cost' ||
            extractionComponentType.value === 'progress_report'),
);

const openExtractionPreview = () => {
    if (!canApplyExtraction.value) {
        return;
    }

    applyError.value = null;
    previewProjectUpdates.value = Object.fromEntries(
        Object.entries(projectUpdates.value).filter(
            ([, value]) =>
                value !== null && value !== undefined && value !== '',
        ),
    ) as Record<string, number | string>;
    previewProgressPercent.value =
        extractionComponentType.value === 'progress_report'
            ? sanitizePercentInput(
                  String(extractedData.value?.metadata.progress_percent ?? ''),
              )
            : '';
    previewAmount.value =
        extractionComponentType.value === 'invoice' ||
        extractionComponentType.value === 'project_cost'
            ? (extractedAmount.value ?? '')
            : '';
    previewBudgetItems.value = budgetItems.value.map((item) => ({ ...item }));
    isPreviewOpen.value = true;
};

const removeDraftField = (
    type: 'project' | 'progress' | 'amount' | 'budget',
    keyOrIndex?: string | number
) => {
    if (type === 'project' && typeof keyOrIndex === 'string') {
        previewProjectUpdates.value[keyOrIndex] = '';
    } else if (type === 'progress') {
        previewProgressPercent.value = ''; 
    } else if (type === 'amount') {
        previewAmount.value = ''; 
    } else if (type === 'budget' && typeof keyOrIndex === 'number') {
        previewBudgetItems.value.splice(keyOrIndex, 1);
    }
};

const runOcr = async (file: File) => {
    applyStatus.value = null;
    applyError.value = null;
    ocrNotice.value = null;

    try {
        await extractOcrFile(file);
    } catch {
        return;
    }
};

type StoredOcrPayload = OcrResponse & {
    document?: UploadedDocument;
};

const loadOcrPayload = (
    payload: StoredOcrPayload,
    successStatus = 'OCR selesai',
) => {
    const text = typeof payload.text === 'string' ? payload.text : '';

    ocrText.value = text;
    ocrEngine.value =
        typeof payload.engine === 'string' ? payload.engine : 'ocr';
    readerStatus.value = `${successStatus} via ${ocrEngine.value}`;
    ocrProgress.value = 100;
    uploadError.value = text.trim()
        ? null
        : 'OCR selesai, tetapi tidak menemukan teks yang bisa dibaca. Silakan input manual.';
};

const prepareExtractionFromDocument = (document: UploadedDocument) => {
    if (document.projectId) {
        selectedProjectId.value = document.projectId;
    }

    if (
        document.documentType &&
        documentTypeOptions.some(
            (option) => option.value === document.documentType,
        )
    ) {
        form.document_type = document.documentType;
    }

    if (document.componentType && document.componentId) {
        const connectionValue = `${document.componentType}:${document.componentId}`;

        if (
            connectionSelectOptions.value.some(
                (option) => option.value === connectionValue,
            )
        ) {
            selectedConnectionValue.value = connectionValue;
        }

        if (document.componentType !== 'project') {
            appliedExtractionComponent.value = {
                type: document.componentType,
                id: document.componentId,
            };
        }
    }
};

const runStoredDocumentOcr = async (document: UploadedDocument) => {
    applyStatus.value = null;
    applyError.value = null;
    ocrNotice.value = null;
    resetOcr();
    uploadError.value = null;
    readerStatus.value = 'Mengirim dokumen tersimpan ke OCR';
    ocrProgress.value = 10;
    isReadingFile.value = true;

    try {
        prepareExtractionFromDocument(document);
        storedOcrDocumentId.value = document.id;

        const response = await csrfFetch(
            `/projects/documents/${document.id}/ocr`,
            {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                },
            },
        );
        const payload = (await response.json().catch(() => ({}))) as
            | StoredOcrPayload
            | { message?: string };

        if (!response.ok) {
            throw new Error(
                typeof payload.message === 'string'
                    ? payload.message
                    : 'OCR dokumen tersimpan gagal.',
            );
        }

        loadOcrPayload(
            payload as StoredOcrPayload,
            'OCR dokumen tersimpan selesai',
        );
        prepareExtractionFromDocument(
            (payload as StoredOcrPayload).document ?? document,
        );
    } catch (error) {
        uploadError.value =
            error instanceof Error
                ? error.message
                : 'OCR dokumen tersimpan gagal.';
        readerStatus.value = 'OCR gagal';
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
        const response = await csrfFetch(
            '/projects/documents/apply-extraction',
            {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    project_id: selectedProjectId.value,
                    component_type: extractionComponentType.value,
                    component_id: extractionComponentId.value,
                    project_updates: previewProjectUpdates.value,
                    items:
                        extractionComponentType.value === 'rab' ||
                        extractionComponentType.value === 'rap'
                            ? previewBudgetItems.value
                            : [],
                    progress_percent:
                        extractionComponentType.value === 'progress_report'
                            ? previewProgressPercent.value || null
                            : null,
                    amount:
                        extractionComponentType.value === 'invoice' ||
                        extractionComponentType.value === 'project_cost'
                            ? previewAmount.value || null
                            : null,
                }),
            },
        );

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(
                typeof payload.message === 'string'
                    ? payload.message
                    : 'Draft OCR belum bisa diterapkan.',
            );
        }

        applyStatus.value =
            typeof payload.message === 'string'
                ? payload.message
                : 'Draft hasil OCR berhasil diterapkan.';
        if (
            typeof payload.component_type === 'string' &&
            typeof payload.component_id === 'number'
        ) {
            appliedExtractionComponent.value = {
                type: payload.component_type,
                id: payload.component_id,
            };
        }
        isPreviewOpen.value = false;
    } catch (error) {
        applyError.value =
            error instanceof Error
                ? error.message
                : 'Draft OCR belum bisa diterapkan.';
    } finally {
        isApplying.value = false;
    }
};

const setFiles = (files: File[]) => {
    if (!hasDocumentType.value) {
        return;
    }

    form.documents = files;
    appliedExtractionComponent.value = null;
    storedOcrDocumentId.value = null;
    resetOcr();
    ocrNotice.value = null;

    if (files[0] && ocrConfigured.value) {
        void runOcr(files[0]);
        return;
    }

    if (files[0]) {
        ocrNotice.value = ocrUnavailableMessage.value;
    }
};

const clearSelectedFiles = () => {
    form.reset('documents');
    form.ocr_text = '';
    form.ocr_engine = '';
    appliedExtractionComponent.value = null;
    storedOcrDocumentId.value = null;
    applyStatus.value = null;
    applyError.value = null;
    ocrNotice.value = null;
    resetOcr();

    if (input.value) {
        input.value.value = '';
    }
};

const handleChange = (event: Event) => {
    if (!hasDocumentType.value) {
        return;
    }

    const target = event.target as HTMLInputElement;
    setFiles(Array.from(target.files ?? []));
};

const handleDrop = (event: DragEvent) => {
    if (!hasDocumentType.value) {
        return;
    }

    setFiles(Array.from(event.dataTransfer?.files ?? []));
};

const upload = () => {
    if (
        !uploadUrl.value ||
        !hasDocumentType.value ||
        form.documents.length === 0
    ) {
        return;
    }

    form.component_type = extractionComponentType.value;
    form.component_id = extractionComponentId.value;
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
            ocrNotice.value = null;
            resetOcr();
            if (input.value) {
                input.value.value = '';
            }
            router.reload();
        },
    });
};

const removeDocument = (document: UploadedDocument) => {
    if (!window.confirm('Hapus dokumen ini?')) {
        return;
    }

    router.delete(`/projects/documents/${document.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="min-w-0 space-y-4 overflow-hidden">
        <div
            class="min-w-0 rounded-lg border border-sidebar-border/70 bg-background p-3 shadow-xs sm:p-4 dark:border-sidebar-border"
        >
            <div class="flex min-w-0 items-center gap-2">
                <FileSearch class="size-4 shrink-0 text-muted-foreground" />
                <h3 class="min-w-0 text-sm font-medium break-words">
                    <TextPreview :text="props.title" :max="64" />
                </h3>
            </div>

            <label
                v-if="canChooseProject"
                class="mt-4 block min-w-0 space-y-1.5"
            >
                <span class="text-xs font-medium text-muted-foreground"
                    >Proyek</span
                >
                <OptionSelect
                    v-model="selectedProjectValue"
                    :options="props.projectOptions"
                    placeholder="Pilih proyek"
                />
            </label>

            <label class="mt-4 block min-w-0 space-y-1.5">
                <span class="text-xs font-medium text-muted-foreground"
                    >Jenis Dokumen</span
                >
                <OptionSelect
                    v-model="form.document_type"
                    :options="documentTypeOptions"
                    allow-empty
                    empty-label="Pilih jenis dokumen"
                    placeholder="Pilih jenis dokumen"
                />
            </label>

            <label
                class="mt-4 flex min-h-40 min-w-0 flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-sidebar-border/80 bg-muted/20 px-4 py-6 text-center transition sm:min-h-48 sm:px-5 sm:py-8 dark:border-sidebar-border"
                :class="
                    hasDocumentType
                        ? 'cursor-pointer hover:bg-muted/40'
                        : 'cursor-not-allowed opacity-60'
                "
                @dragover.prevent
                @drop.prevent="handleDrop"
            >
                <input
                    ref="input"
                    type="file"
                    multiple
                    class="sr-only"
                    :disabled="!hasDocumentType"
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
                <span class="text-sm font-medium break-words">
                    {{ statusText }}
                </span>
                <span
                    class="max-w-sm text-xs break-words text-muted-foreground"
                >
                    <TextPreview :text="props.description" :max="96" />
                </span>
            </label>

            <div
                v-if="
                    form.documents.length > 0 ||
                    form.processing ||
                    form.errors.documents ||
                    ocrNotice ||
                    hasOcrState
                "
                class="mt-4 min-w-0 space-y-3"
            >
                <div
                    v-if="selectedFileNames"
                    class="flex min-w-0 items-start justify-between gap-3 rounded-md border border-sidebar-border/60 px-3 py-2 text-sm dark:border-sidebar-border"
                >
                    <span class="min-w-0">
                        <span class="block text-xs text-muted-foreground">
                            File dipilih
                        </span>
                        <TextPreview
                            :text="selectedFileNames"
                            :max="72"
                            class="mt-0.5 font-medium"
                        />
                    </span>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        class="shrink-0 text-muted-foreground"
                        @click="clearSelectedFiles"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>

                <div
                    v-if="form.processing || isReadingFile || readerStatus"
                    class="min-w-0 rounded-md bg-muted/40 px-3 py-2"
                >
                    <div class="flex min-w-0 items-center gap-2 text-sm">
                        <LoaderCircle
                            v-if="form.processing || isReadingFile"
                            class="size-4 animate-spin text-muted-foreground"
                        />
                        <ScanText v-else class="size-4 text-muted-foreground" />
                        <span class="min-w-0 break-words">{{
                            form.processing
                                ? 'Menyimpan dokumen ke proyek...'
                                : (readerStatus ?? 'OCR siap digunakan')
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

                <p
                    v-if="ocrNotice"
                    class="rounded-md border border-sidebar-border/60 bg-muted/30 px-3 py-2 text-sm text-muted-foreground dark:border-sidebar-border"
                >
                    {{ ocrNotice }}
                </p>

                <div
                    v-if="ocrPreview"
                    class="min-w-0 rounded-md border border-sidebar-border/60 px-3 py-2 text-sm dark:border-sidebar-border"
                >
                    <span class="block text-xs text-muted-foreground"
                        >Preview teks OCR</span
                    >
                    <p
                        class="mt-1 max-h-32 overflow-y-auto text-xs leading-relaxed whitespace-pre-wrap text-muted-foreground"
                    >
                        {{ ocrPreview }}
                    </p>
                </div>

                <div
                    v-if="ocrText"
                    class="min-w-0 rounded-md border border-sidebar-border/60 px-3 py-2 text-sm dark:border-sidebar-border"
                >
                    <span class="block text-xs text-muted-foreground"
                        >Draft hasil OCR</span
                    >
                    <p class="mt-0.5 text-sm">{{ applySummary }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            size="sm"
                            :disabled="!canApplyExtraction || isApplying"
                            @click="openExtractionPreview"
                        >
                            <ScanText class="mr-2 size-4" />
                            Review Draft OCR
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
                        !hasDocumentType ||
                        !uploadUrl
                    "
                    @click="upload"
                >
                    <Upload class="mr-2 size-4" />
                    Unggah
                </Button>
            </div>
        </div>

        <div
            class="min-w-0 rounded-lg border border-sidebar-border/70 bg-background p-3 shadow-xs sm:p-4 dark:border-sidebar-border"
        >
            <div class="flex min-w-0 items-center gap-2">
                <FileText class="size-4 shrink-0 text-muted-foreground" />
                <h3 class="text-sm font-medium">Dokumen Proyek</h3>
            </div>

            <div class="mt-4 grid min-w-0 gap-2">
                <div
                    v-for="document in props.documents"
                    :key="document.id"
                    class="flex min-w-0 flex-col gap-3 rounded-md border border-sidebar-border/60 px-3 py-2 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border"
                >
                    <a
                        :href="document.url"
                        target="_blank"
                        rel="noreferrer"
                        :title="`${document.originalName} - ${documentSummary(document)}`"
                        class="flex min-w-0 flex-1 items-start gap-3 overflow-hidden"
                    >
                        <FileText
                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                        />
                        <span class="min-w-0 flex-1 overflow-hidden">
                            <span
                                class="block max-w-full text-sm font-medium break-all whitespace-normal text-foreground"
                            >
                                <TextPreview
                                    :text="document.originalName"
                                    :max="64"
                                />
                            </span>
                            <span
                                class="block max-w-full text-xs break-words whitespace-normal text-muted-foreground"
                            >
                                <TextPreview
                                    :text="documentSummary(document)"
                                    :max="96"
                                />
                            </span>
                        </span>
                    </a>
                    <div
                        class="flex shrink-0 items-center gap-1 self-end sm:self-auto"
                    >
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :disabled="
                                isReadingFile &&
                                storedOcrDocumentId === document.id
                            "
                            @click="runStoredDocumentOcr(document)"
                        >
                            <LoaderCircle
                                v-if="
                                    isReadingFile &&
                                    storedOcrDocumentId === document.id
                                "
                                class="mr-2 size-4 animate-spin"
                            />
                            <RotateCcw v-else class="mr-2 size-4" />
                            OCR ulang
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            class="text-destructive"
                            @click="removeDocument(document)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>

                <div
                    v-if="props.documents.length === 0"
                    class="rounded-md border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground"
                >
                    {{ props.emptyText }}
                </div>
            </div>
        </div>

        <Dialog v-model:open="isPreviewOpen">
            <DialogContent
                class="flex max-h-[calc(100dvh-2rem)] w-[calc(100vw-2rem)] !max-w-6xl flex-col overflow-hidden p-4 sm:p-6"
            >
                <DialogHeader class="shrink-0">
                    <DialogTitle>Review Draft Hasil OCR</DialogTitle>
                </DialogHeader>

                <div
                    class="min-h-0 flex-1 space-y-5 overflow-x-hidden overflow-y-auto pr-1"
                >
                    <section
                        v-if="projectUpdatePreview.length > 0"
                        class="min-w-0 space-y-3"
                    >
                        <h4 class="text-sm font-medium">Field proyek</h4>

                        <div class="grid min-w-0 gap-3 lg:grid-cols-2">
                            <div
                                v-for="field in projectUpdatePreview"
                                :key="field.label"
                                class="flex min-w-0 items-start gap-2"
                                :class="{ 'lg:col-span-2': field.label === 'Lokasi' }"
                            >
                                <label class="min-w-0 flex-1 space-y-1.5">
                                    <span class="text-xs font-medium text-muted-foreground">
                                        {{ field.label }}
                                    </span>

                                    <textarea
                                        v-if="field.label === 'Lokasi'"
                                        v-model="previewProjectUpdates[field.key]"
                                        class="min-h-24 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                    />

                                    <input
                                        v-else
                                        v-model="previewProjectUpdates[field.key]"
                                        class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                    />
                                </label>

                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="mt-6 shrink-0 text-destructive"
                                    @click="removeDraftField('project', field.key)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </div>
                    </section>

                    <section
                        v-if="metadataPreview.length > 0"
                        class="grid min-w-0 gap-3 sm:grid-cols-2"
                    >
                        <div
                            v-if="extractionComponentType === 'progress_report' && previewProgressPercent !== null"
                            class="flex min-w-0 items-start gap-2"
                        >
                            <label class="min-w-0 flex-1 space-y-1.5">
                                <span class="text-xs font-medium text-muted-foreground">
                                    Persentase Progress
                                </span>

                                <input
                                    v-model="previewProgressPercent"
                                    type="text"
                                    inputmode="decimal"
                                    pattern="^\d{0,3}(\.\d{0,2})?$"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                    @input="handleProgressPercentInput"
                                />
                            </label>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                class="mt-6 shrink-0 text-destructive"
                                @click="removeDraftField('progress')"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>

                        <div
                            v-if="(extractionComponentType === 'invoice' || extractionComponentType === 'project_cost') && previewAmount !== null"
                            class="flex min-w-0 items-start gap-2"
                        >
                            <label class="min-w-0 flex-1 space-y-1.5">
                                <span class="text-xs font-medium text-muted-foreground">
                                    Nilai
                                </span>

                                <input
                                    v-model="previewAmount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                />
                            </label>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                class="mt-6 shrink-0 text-destructive"
                                @click="removeDraftField('amount')"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </section>

                    <section
                        v-if="
                            (extractionComponentType === 'rab' ||
                                extractionComponentType === 'rap') &&
                            previewBudgetItems.length > 0
                        "
                        class="min-w-0 space-y-3"
                    >
                        <h4 class="text-sm font-medium">Baris anggaran</h4>

                        <div
                            class="max-h-[38dvh] max-w-full overflow-auto rounded-md border border-sidebar-border/60 dark:border-sidebar-border"
                        >
                            <table class="min-w-[60rem] table-fixed text-sm">
                                <thead
                                    class="sticky top-0 z-10 bg-muted text-left text-xs"
                                >
                                    <tr>
                                        <th class="w-[20rem] px-3 py-2">
                                            Uraian
                                        </th>
                                        <th class="w-[11rem] px-3 py-2">
                                            Bagian
                                        </th>
                                        <th class="w-[7rem] px-3 py-2">Qty</th>
                                        <th class="w-[7rem] px-3 py-2">Unit</th>
                                        <th class="w-[10rem] px-3 py-2">
                                            Harga Satuan
                                        </th>
                                        <th class="w-[10rem] px-3 py-2">
                                            Total
                                        </th>
                                        <th class="w-[5rem] px-3 py-2"></th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="(
                                            item, index
                                        ) in previewBudgetItems"
                                        :key="`${item.description}-${index}`"
                                        class="border-t border-sidebar-border/50 align-top"
                                    >
                                        <td class="px-3 py-2">
                                            <textarea
                                                v-model="item.description"
                                                class="min-h-12 w-full min-w-0 resize-y rounded-md border border-input bg-transparent px-2 py-1 text-sm"
                                            />
                                        </td>

                                        <td class="px-3 py-2">
                                            <input
                                                v-model="item.sub_category"
                                                class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2 text-sm"
                                            />
                                        </td>

                                        <td class="px-3 py-2">
                                            <input
                                                v-model="item.quantity"
                                                type="number"
                                                step="0.01"
                                                class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2 text-sm"
                                            />
                                        </td>

                                        <td class="px-3 py-2">
                                            <input
                                                v-model="item.unit"
                                                class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2 text-sm"
                                            />
                                        </td>

                                        <td class="px-3 py-2">
                                            <input
                                                v-model="item.unit_price"
                                                type="number"
                                                step="0.01"
                                                class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2 text-sm"
                                            />
                                        </td>

                                        <td class="px-3 py-2">
                                            <input
                                                v-model="item.total_price"
                                                type="number"
                                                step="0.01"
                                                class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2 text-sm"
                                            />
                                        </td>

                                        <td class="px-3 py-2">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon-sm"
                                                class="text-destructive"
                                                @click="removeDraftField('budget', index)"
                                            >
                                                <Trash2 class="size-4" />
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <p
                        v-if="
                            projectUpdatePreview.length === 0 &&
                            metadataPreview.length === 0 &&
                            previewBudgetItems.length === 0
                        "
                        class="rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                    >
                        Tidak ada field atau baris yang terdeteksi dari hasil
                        OCR ini.
                    </p>
                </div>

                <section
                    v-if="(extractionComponentType === 'rab' || extractionComponentType === 'rap') && previewBudgetItems.length > 0"
                    class="min-w-0 space-y-3"
                >
                    <h4 class="text-sm font-medium">Baris anggaran</h4>

                    <div class="max-h-[38dvh] max-w-full overflow-auto rounded-md border border-sidebar-border/60 dark:border-sidebar-border">
                        <table class="min-w-[60rem] table-fixed text-sm">
                            <thead class="sticky top-0 z-10 bg-muted text-left text-xs">
                                <tr>
                                    <th class="w-[20rem] px-3 py-2">Uraian</th>
                                    <th class="w-[11rem] px-3 py-2">Bagian</th>
                                    <th class="w-[7rem] px-3 py-2">Qty</th>
                                    <th class="w-[7rem] px-3 py-2">Unit</th>
                                    <th class="w-[10rem] px-3 py-2">Harga Satuan</th>
                                    <th class="w-[10rem] px-3 py-2">Total</th>
                                    <th class="w-[5rem] px-3 py-2"></th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="(item, index) in previewBudgetItems"
                                    :key="`${item.description}-${index}`"
                                    class="border-t border-sidebar-border/50 align-top"
                                >
                                    <td class="px-3 py-2">
                                        <textarea
                                            v-model="item.description"
                                            class="min-h-12 w-full min-w-0 resize-y rounded-md border border-input bg-transparent px-2 py-1 text-sm"
                                        />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input
                                            v-model="item.sub_category"
                                            class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2 text-sm"
                                        />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input
                                            v-model="item.quantity"
                                            type="number"
                                            step="0.01"
                                            class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2 text-sm"
                                        />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input
                                            v-model="item.unit"
                                            class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2 text-sm"
                                        />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input
                                            v-model="item.unit_price"
                                            type="number"
                                            step="0.01"
                                            class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2 text-sm"
                                        />
                                    </td>

                                    <td class="px-3 py-2">
                                        <input
                                            v-model="item.total_price"
                                            type="number"
                                            step="0.01"
                                            class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-2 text-sm"
                                        />
                                    </td>

                                    <td class="px-3 py-2">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            class="text-destructive"
                                            @click="removeDraftField('budget', index)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <DialogFooter class="mt-4 shrink-0">
                    <Button
                        type="button"
                        variant="outline"
                        @click="isPreviewOpen = false"
                    >
                        Batal
                    </Button>

                    <Button
                        type="button"
                        :disabled="isApplying"
                        @click="applyExtraction"
                    >
                        <LoaderCircle
                            v-if="isApplying"
                            class="mr-2 size-4 animate-spin"
                        />
                        Terapkan Draft
                    </Button>
                    
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
