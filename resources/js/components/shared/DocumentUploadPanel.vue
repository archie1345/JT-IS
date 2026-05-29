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
import { csrfToken } from '@/lib/ocr';
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

const input = ref<HTMLInputElement | null>(null);
const applyStatus = ref<null | string>(null);
const applyError = ref<null | string>(null);
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
    { value: 'rap', label: 'RAP' },
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

const documentTypeLabel = (value?: null | string) =>
    documentTypeOptions.find((option) => option.value === value)?.label ??
    value ??
    'Dokumen';

const ocrStatusLabel = (document: UploadedDocument) =>
    document.ocrProcessedAt
        ? `OCR diproses${document.ocrEngine ? ` (${document.ocrEngine})` : ''}`
        : 'OCR belum diproses';

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
    document_type: defaultDocumentTypeFor(props.componentType),
    ocr_text: '',
    ocr_engine: '',
});

const canChooseProject = computed(
    () => !props.projectId && props.projectOptions.length > 0,
);
const canChooseConnection = computed(() => props.connectionOptions.length > 0);
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
const uploadUrl = computed(() =>
    selectedProjectId.value
        ? `/projects/${selectedProjectId.value}/documents`
        : '',
);

watch(selectedConnection, (connection) => {
    if (connection?.projectId) {
        selectedProjectId.value = connection.projectId;
    }

    if (connection?.componentType) {
        form.document_type = defaultDocumentTypeFor(connection.componentType);
    }
});
const selectedFileNames = computed(() =>
    form.documents.map((file) => file.name).join(', '),
);
const statusText = computed(() => {
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
                effectiveComponentType.value === 'progress_report' &&
                previewProgressPercent.value !== ''
                    ? previewProgressPercent.value
                    : null,
        },
        {
            label: 'Amount',
            value:
                (effectiveComponentType.value === 'invoice' ||
                    effectiveComponentType.value === 'project_cost') &&
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
        (effectiveComponentType.value === 'project' ||
            effectiveComponentType.value === 'rab' ||
            effectiveComponentType.value === 'rap' ||
            effectiveComponentType.value === 'invoice' ||
            effectiveComponentType.value === 'project_cost' ||
            effectiveComponentType.value === 'progress_report'),
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
        effectiveComponentType.value === 'progress_report'
            ? sanitizePercentInput(
                  String(extractedData.value?.metadata.progress_percent ?? ''),
              )
            : '';
    previewAmount.value =
        effectiveComponentType.value === 'invoice' ||
        effectiveComponentType.value === 'project_cost'
            ? (extractedAmount.value ?? '')
            : '';
    previewBudgetItems.value = budgetItems.value.map((item) => ({ ...item }));
    isPreviewOpen.value = true;
};

const removePreviewBudgetItem = (index: number) => {
    previewBudgetItems.value.splice(index, 1);
};

const runOcr = async (file: File) => {
    applyStatus.value = null;
    applyError.value = null;

    try {
        await extractOcrFile(file);
    } catch {
        return;
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
                project_updates: previewProjectUpdates.value,
                items:
                    effectiveComponentType.value === 'rab' ||
                    effectiveComponentType.value === 'rap'
                        ? previewBudgetItems.value
                        : [],
                progress_percent:
                    effectiveComponentType.value === 'progress_report'
                        ? previewProgressPercent.value || null
                        : null,
                amount:
                    effectiveComponentType.value === 'invoice' ||
                    effectiveComponentType.value === 'project_cost'
                        ? previewAmount.value || null
                        : null,
            }),
        });

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
        isPreviewOpen.value = false;
        router.reload();
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
    form.documents = files;
    resetOcr();

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
                    >Proyek</span
                >
                <OptionSelect
                    v-model="selectedProjectValue"
                    :options="props.projectOptions"
                    placeholder="Pilih proyek"
                />
            </label>

            <label v-if="canChooseConnection" class="mt-4 block space-y-1.5">
                <span class="text-xs font-medium text-muted-foreground"
                    >Hubungkan ke</span
                >
                <OptionSelect
                    v-model="selectedConnectionValue"
                    :options="connectionSelectOptions"
                    placeholder="Pilih koneksi"
                />
            </label>

            <label class="mt-4 block space-y-1.5">
                <span class="text-xs font-medium text-muted-foreground"
                    >Jenis Dokumen</span
                >
                <OptionSelect
                    v-model="form.document_type"
                    :options="documentTypeOptions"
                    placeholder="Pilih jenis dokumen"
                />
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
                        >File dipilih</span
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

                <div
                    v-if="ocrPreview"
                    class="rounded-md border border-sidebar-border/60 px-3 py-2 text-sm dark:border-sidebar-border"
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
                    class="rounded-md border border-sidebar-border/60 px-3 py-2 text-sm dark:border-sidebar-border"
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
                            Review draft OCR
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
                    Unggah
                </Button>
            </div>
        </div>

        <div
            class="rounded-lg border border-sidebar-border/70 bg-background p-4 shadow-xs dark:border-sidebar-border"
        >
            <div class="flex items-center gap-2">
                <FileText class="size-4 text-muted-foreground" />
                <h3 class="text-sm font-medium">Dokumen Proyek</h3>
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
                                {{ document.createdAt ?? 'diunggah' }}
                                /
                                {{ documentTypeLabel(document.documentType) }}
                                /
                                {{
                                    document.size
                                        ? Math.round(document.size / 1024) +
                                          ' KB'
                                        : 'file'
                                }}
                                /
                                {{ ocrStatusLabel(document) }}
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

        <Dialog v-model:open="isPreviewOpen">
            <DialogContent
                class="max-h-[calc(100vh-2rem)] w-[calc(100vw-2rem)] overflow-y-auto sm:max-w-4xl"
            >
                <DialogHeader>
                    <DialogTitle>Review Draft Hasil OCR</DialogTitle>
                </DialogHeader>

                <div class="space-y-5">
                    <section
                        v-if="projectUpdatePreview.length > 0"
                        class="space-y-3"
                    >
                        <h4 class="text-sm font-medium">Field proyek</h4>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label
                                v-for="field in projectUpdatePreview"
                                :key="field.label"
                                class="space-y-1.5"
                            >
                                <span
                                    class="text-xs font-medium text-muted-foreground"
                                    >{{ field.label }}</span
                                >
                                <textarea
                                    v-if="field.label === 'Lokasi'"
                                    v-model="previewProjectUpdates[field.key]"
                                    class="min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                />
                                <input
                                    v-else
                                    v-model="previewProjectUpdates[field.key]"
                                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                />
                            </label>
                        </div>
                    </section>

                    <section
                        v-if="metadataPreview.length > 0"
                        class="grid gap-3 sm:grid-cols-2"
                    >
                        <label
                            v-if="effectiveComponentType === 'progress_report'"
                            class="space-y-1.5"
                        >
                            <span
                                class="text-xs font-medium text-muted-foreground"
                                >Persentase Progress</span
                            >
                            <input
                                v-model="previewProgressPercent"
                                type="text"
                                inputmode="decimal"
                                pattern="^\d{0,3}(\.\d{0,2})?$"
                                min="0"
                                max="100"
                                step="0.01"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                @input="handleProgressPercentInput"
                            />
                        </label>

                        <label
                            v-if="
                                effectiveComponentType === 'invoice' ||
                                effectiveComponentType === 'project_cost'
                            "
                            class="space-y-1.5"
                        >
                            <span
                                class="text-xs font-medium text-muted-foreground"
                                >Nilai</span
                            >
                            <input
                                v-model="previewAmount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                            />
                        </label>
                    </section>

                    <section
                        v-if="
                            (effectiveComponentType === 'rab' ||
                                effectiveComponentType === 'rap') &&
                            previewBudgetItems.length > 0
                        "
                        class="space-y-3"
                    >
                        <h4 class="text-sm font-medium">Baris anggaran</h4>
                        <div class="overflow-x-auto rounded-md border">
                            <table class="min-w-[54rem] text-sm">
                                <thead class="bg-muted/50 text-left text-xs">
                                    <tr>
                                        <th class="px-3 py-2">Uraian</th>
                                        <th class="px-3 py-2">Bagian</th>
                                        <th class="px-3 py-2">Qty</th>
                                        <th class="px-3 py-2">Unit</th>
                                        <th class="px-3 py-2">Unit Price</th>
                                        <th class="px-3 py-2">Total</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(
                                            item, index
                                        ) in previewBudgetItems"
                                        :key="`${item.description}-${index}`"
                                        class="border-t align-top"
                                    >
                                        <td class="px-3 py-2">
                                            <textarea
                                                v-model="item.description"
                                                class="min-h-16 w-full rounded-md border border-input bg-transparent px-2 py-1 text-sm"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                v-model="item.sub_category"
                                                class="h-9 w-36 rounded-md border border-input bg-transparent px-2 text-sm"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                v-model="item.quantity"
                                                type="number"
                                                step="0.01"
                                                class="h-9 w-24 rounded-md border border-input bg-transparent px-2 text-sm"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                v-model="item.unit"
                                                class="h-9 w-20 rounded-md border border-input bg-transparent px-2 text-sm"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                v-model="item.unit_price"
                                                type="number"
                                                step="0.01"
                                                class="h-9 w-32 rounded-md border border-input bg-transparent px-2 text-sm"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <input
                                                v-model="item.total_price"
                                                type="number"
                                                step="0.01"
                                                class="h-9 w-32 rounded-md border border-input bg-transparent px-2 text-sm"
                                            />
                                        </td>
                                        <td class="px-3 py-2">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="
                                                    removePreviewBudgetItem(
                                                        index,
                                                    )
                                                "
                                            >
                                                Hapus
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

                <DialogFooter>
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
