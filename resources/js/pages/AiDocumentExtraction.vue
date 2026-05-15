<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    ClipboardPaste,
    FileSearch,
    LoaderCircle,
    RotateCcw,
    ScanText,
    Upload,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    extractImportantDocumentData,
    type ExtractedDocumentMetadata,
} from '@/lib/documentExtraction';
import type { BreadcrumbItem } from '@/types';

type PdfDocument = {
    numPages: number;
    getPage: (pageNumber: number) => Promise<{
        getTextContent: () => Promise<{ items: unknown[] }>;
        getViewport: (options: { scale: number }) => {
            height: number;
            width: number;
        };
        render: (options: {
            canvasContext: CanvasRenderingContext2D;
            viewport: {
                height: number;
                width: number;
            };
        }) => {
            promise: Promise<void>;
        };
    }>;
};

type PdfJsModule = {
    GlobalWorkerOptions: {
        workerSrc: string;
    };
    getDocument: (source: { data: ArrayBuffer }) => {
        promise: Promise<PdfDocument>;
    };
};

const pdfJsVersion = '5.7.284';
const pdfJsUrl = `https://cdn.jsdelivr.net/npm/pdfjs-dist@${pdfJsVersion}/build/pdf.mjs`;
const pdfWorkerUrl = `https://cdn.jsdelivr.net/npm/pdfjs-dist@${pdfJsVersion}/build/pdf.worker.mjs`;
let pdfJsModule: null | Promise<PdfJsModule> = null;

type OcrProgress = {
    progress?: number;
    status?: string;
};

type TesseractWorker = {
    recognize: (image: Blob | HTMLCanvasElement) => Promise<{
        data: {
            text: string;
        };
    }>;
    terminate: () => Promise<void>;
};

type TesseractModule = {
    createWorker?: (
        lang?: string,
        oem?: number,
        options?: {
            logger?: (progress: OcrProgress) => void;
        },
    ) => Promise<TesseractWorker>;
    recognize?: (
        image: Blob | HTMLCanvasElement,
        lang?: string,
        options?: {
            logger?: (progress: OcrProgress) => void;
        },
    ) => Promise<{
        data: {
            text: string;
        };
    }>;
    default?: TesseractModule;
};

const tesseractVersion = '5.1.1';
const tesseractUrl = `https://cdn.jsdelivr.net/npm/tesseract.js@${tesseractVersion}/dist/tesseract.esm.min.js`;
let tesseractModule: null | Promise<TesseractModule> = null;

const loadPdfJs = async (): Promise<PdfJsModule> => {
    pdfJsModule ??= import(/* @vite-ignore */ pdfJsUrl).then((module) => {
        const pdfJs = module as PdfJsModule;

        pdfJs.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;

        return pdfJs;
    });

    return pdfJsModule;
};

const loadTesseract = async (): Promise<TesseractModule> => {
    tesseractModule ??= import(/* @vite-ignore */ tesseractUrl).then((module) => {
        const tesseract = module as TesseractModule;

        return tesseract.recognize || tesseract.createWorker
            ? tesseract
            : (tesseract.default as TesseractModule);
    });

    return tesseractModule;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'AI Extraction', href: '/ai-document-extraction' },
];

const sampleText = `BERITA ACARA PEMERIKSAAN PEKERJAAN
Nomor : 11.24/BA/MC-80,1%/DJATA/XI/2025
Progress pekerjaan Mutual Check 80,1%

Pengguna Jasa / Owner
Nama : Ir. Indra Nurdianyoto

PMC / Konsultan Manajemen Konstruksi
Nama : Herman Pribadi

Penyedia Jasa / Kontraktor
Nama : Dony Trio Prabowo

PEKERJAAN PERSIAPAN
Pengukuran Penampang Melintang Sungai  Cross  13,00  455.000  5.915.000`;

const sourceText = ref('');
const selectedFileName = ref<null | string>(null);
const uploadError = ref<null | string>(null);
const isReadingFile = ref(false);
const readerStatus = ref<null | string>(null);
const ocrProgress = ref(0);

const extractedData = computed(() => extractImportantDocumentData(sourceText.value));
const prettyJson = computed(() => JSON.stringify(extractedData.value, null, 2));
const hasExtractedText = computed(() => sourceText.value.trim().length > 0);
const hasUploadState = computed(() => selectedFileName.value || uploadError.value || hasExtractedText.value);

const metadataLabels: Record<keyof ExtractedDocumentMetadata, string> = {
    doc_number: 'Document Number',
    progress_percent: 'Progress',
    pic_owner: 'PIC Owner',
    pic_pmc: 'PIC PMC',
    pic_contractor: 'PIC Contractor',
};

const metadataRows = computed(() =>
    (Object.keys(metadataLabels) as (keyof ExtractedDocumentMetadata)[]).map((key) => ({
        key,
        label: metadataLabels[key],
        value: extractedData.value.metadata[key],
    })),
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

const formatPreviewValue = (value: null | number | string) => {
    if (value === null || value === '') {
        return 'Not found';
    }

    return String(value);
};

const countFoundFields = (fields: { value: null | number | string }[]) =>
    fields.filter((field) => field.value !== null && field.value !== '').length;

const loadSample = () => {
    sourceText.value = sampleText;
    selectedFileName.value = 'Sample MC document';
    uploadError.value = null;
};

const clearText = () => {
    sourceText.value = '';
    selectedFileName.value = null;
    uploadError.value = null;
    readerStatus.value = null;
    ocrProgress.value = 0;
};

const isPdfFile = (file: File) =>
    file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');

const isImageFile = (file: File) =>
    file.type.startsWith('image/') || /\.(png|jpe?g|webp|bmp|tiff?)$/i.test(file.name);

const isTextItem = (item: unknown): item is { str: string } =>
    typeof item === 'object' &&
    item !== null &&
    'str' in item &&
    typeof (item as { str?: unknown }).str === 'string';

const extractPdfText = async (file: File): Promise<string> => {
    const pdfJs = await loadPdfJs();
    const document = await pdfJs.getDocument({ data: await file.arrayBuffer() }).promise;
    const pageTexts: string[] = [];

    for (let pageNumber = 1; pageNumber <= document.numPages; pageNumber += 1) {
        readerStatus.value = `Reading PDF text layer, page ${pageNumber} of ${document.numPages}`;
        const page = await document.getPage(pageNumber);
        const content = await page.getTextContent();
        const text = content.items
            .map((item) => (isTextItem(item) ? item.str : ''))
            .join(' ')
            .replace(/\s{3,}/g, '  ')
            .trim();

        if (text) {
            pageTexts.push(text);
        }
    }

    return pageTexts.join('\n\n');
};

const renderPdfPageToCanvas = async (document: PdfDocument, pageNumber: number) => {
    const page = await document.getPage(pageNumber);
    const viewport = page.getViewport({ scale: 2 });
    const canvas = window.document.createElement('canvas');
    const context = canvas.getContext('2d');

    if (!context) {
        throw new Error('Unable to create a canvas for OCR.');
    }

    canvas.width = viewport.width;
    canvas.height = viewport.height;

    await page.render({
        canvasContext: context,
        viewport,
    }).promise;

    return canvas;
};

const runOcr = async (image: Blob | HTMLCanvasElement, label: string): Promise<string> => {
    const tesseract = await loadTesseract();

    readerStatus.value = `Running OCR on ${label}`;

    const logger = (progress: OcrProgress) => {
        if (progress.status) {
            readerStatus.value = `${label}: ${progress.status}`;
        }

        if (typeof progress.progress === 'number') {
            ocrProgress.value = Math.round(progress.progress * 100);
        }
    };

    if (tesseract.recognize) {
        const result = await tesseract.recognize(image, 'eng+ind', { logger });

        return result.data.text.trim();
    }

    if (!tesseract.createWorker) {
        throw new Error('OCR engine did not load correctly.');
    }

    const worker = await tesseract.createWorker('eng+ind', undefined, { logger });

    try {
        const result = await worker.recognize(image);

        return result.data.text.trim();
    } finally {
        await worker.terminate();
    }
};

const extractPdfTextWithOcr = async (file: File): Promise<string> => {
    const textLayer = await extractPdfText(file);

    if (textLayer.trim().length > 40) {
        return textLayer;
    }

    const pdfJs = await loadPdfJs();
    const document = await pdfJs.getDocument({ data: await file.arrayBuffer() }).promise;
    const pageTexts: string[] = [];

    ocrProgress.value = 0;

    for (let pageNumber = 1; pageNumber <= document.numPages; pageNumber += 1) {
        const canvas = await renderPdfPageToCanvas(document, pageNumber);
        const text = await runOcr(canvas, `page ${pageNumber} of ${document.numPages}`);

        if (text) {
            pageTexts.push(text);
        }
    }

    return pageTexts.join('\n\n');
};

const readDocumentFile = async (file: File) => {
    selectedFileName.value = file.name;
    uploadError.value = null;
    isReadingFile.value = true;
    readerStatus.value = 'Preparing document';
    ocrProgress.value = 0;

    try {
        if (isPdfFile(file)) {
            sourceText.value = await extractPdfTextWithOcr(file);
        } else if (isImageFile(file)) {
            sourceText.value = await runOcr(file, file.name);
        } else {
            readerStatus.value = 'Reading text file';
            sourceText.value = await file.text();
        }

        if (!sourceText.value.trim()) {
            uploadError.value = 'No readable text was found. This may be an image-only scan that needs OCR.';
        }
    } catch (error) {
        sourceText.value = '';
        uploadError.value = error instanceof Error ? error.message : 'Unable to read this document.';
    } finally {
        isReadingFile.value = false;
        readerStatus.value = sourceText.value.trim() ? 'Extraction complete' : readerStatus.value;
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
            <section class="border-b border-sidebar-border/70 pb-5 dark:border-sidebar-border">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">AI Document Extraction</h1>
                        <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                            Upload progress, BAHPP, RAB, or offer documents to test the metadata schema before user confirmation.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button type="button" variant="outline" @click="loadSample">
                            <ClipboardPaste class="mr-2 size-4" />
                            Load Sample
                        </Button>
                        <Button type="button" variant="outline" @click="clearText">
                            <RotateCcw class="mr-2 size-4" />
                            Clear
                        </Button>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
                <aside class="flex flex-col gap-4">
                    <div class="rounded-lg border border-sidebar-border/70 bg-background p-4 dark:border-sidebar-border">
                        <div class="flex items-center gap-2">
                            <FileSearch class="size-4 text-muted-foreground" />
                            <h2 class="text-sm font-medium">Document Upload</h2>
                        </div>

                        <label
                            class="mt-4 flex min-h-48 cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-sidebar-border/80 bg-muted/20 px-5 py-8 text-center transition hover:bg-muted/40 dark:border-sidebar-border"
                            @dragover.prevent
                            @drop.prevent="handleDrop"
                        >
                            <input class="sr-only" type="file" accept=".pdf,.txt,.csv,.png,.jpg,.jpeg,.webp,.bmp,.tif,.tiff,text/plain,application/pdf,image/*" @change="handleFileInput" />
                            <span class="flex size-11 items-center justify-center rounded-full bg-background shadow-sm">
                                <LoaderCircle v-if="isReadingFile" class="size-5 animate-spin text-muted-foreground" />
                                <Upload v-else class="size-5 text-muted-foreground" />
                            </span>
                            <span class="text-sm font-medium">
                                {{ isReadingFile ? 'Reading document...' : 'Upload document' }}
                            </span>
                            <span class="max-w-sm text-xs text-muted-foreground">
                                PDF, image, text, and CSV files are supported. Scanned pages use OCR automatically.
                            </span>
                        </label>

                        <div v-if="hasUploadState" class="mt-4 space-y-3">
                            <div v-if="selectedFileName" class="rounded-md border border-sidebar-border/60 px-3 py-2 text-sm dark:border-sidebar-border">
                                <span class="block text-xs text-muted-foreground">Selected file</span>
                                <span class="mt-0.5 block break-words font-medium">{{ selectedFileName }}</span>
                            </div>

                            <div v-if="readerStatus" class="rounded-md bg-muted/40 px-3 py-2">
                                <div class="flex items-center gap-2 text-sm">
                                    <ScanText class="size-4 text-muted-foreground" />
                                    <span>{{ readerStatus }}</span>
                                </div>
                                <div v-if="isReadingFile && ocrProgress > 0" class="mt-2 h-1.5 overflow-hidden rounded-full bg-muted">
                                    <div class="h-full rounded-full bg-primary transition-all" :style="{ width: `${ocrProgress}%` }" />
                                </div>
                            </div>

                            <p v-if="uploadError" class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                                {{ uploadError }}
                            </p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-sidebar-border/70 bg-background p-4 dark:border-sidebar-border">
                        <h2 class="text-sm font-medium">Extracted Metadata</h2>
                        <dl class="mt-4 grid gap-3">
                            <div v-for="row in metadataRows" :key="row.key" class="grid gap-1 rounded-md border border-sidebar-border/60 px-3 py-2 dark:border-sidebar-border">
                                <dt class="text-xs font-medium uppercase text-muted-foreground">
                                    {{ row.label }}
                                </dt>
                                <dd class="break-words text-sm font-medium">
                                    {{ formatMetadataValue(row.key, row.value) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </aside>

                <div class="flex min-w-0 flex-col gap-6">
                    <div class="rounded-lg border border-sidebar-border/70 bg-background p-4 dark:border-sidebar-border">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-sm font-medium">Grouping Preview</h2>
                            <span class="text-xs text-muted-foreground">
                                {{ extractedData.preview_groups.length }} document groups
                            </span>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div
                                v-for="group in extractedData.preview_groups"
                                :key="group.category"
                                class="rounded-md border border-sidebar-border/60 p-3 dark:border-sidebar-border"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-medium">{{ group.category }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ group.description }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-md bg-muted px-2 py-1 text-xs text-muted-foreground">
                                        {{ countFoundFields(group.fields) }}/{{ group.fields.length }}
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <span
                                        v-for="table in group.target_tables"
                                        :key="table"
                                        class="rounded-md border border-sidebar-border/60 px-2 py-1 text-xs text-muted-foreground dark:border-sidebar-border"
                                    >
                                        {{ table }}
                                    </span>
                                </div>

                                <dl class="mt-3 grid gap-2">
                                    <div v-for="field in group.fields" :key="field.label" class="grid gap-1 rounded-md bg-muted/30 px-3 py-2">
                                        <dt class="text-xs text-muted-foreground">{{ field.label }}</dt>
                                        <dd class="break-words text-xs font-medium">
                                            {{ formatPreviewValue(field.value) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <details v-if="hasExtractedText" class="rounded-lg border border-sidebar-border/70 bg-background dark:border-sidebar-border">
                        <summary class="cursor-pointer px-4 py-3 text-sm font-medium">Extracted Text Preview</summary>
                        <pre class="max-h-80 overflow-auto border-t border-sidebar-border/60 p-4 text-xs leading-5 whitespace-pre-wrap dark:border-sidebar-border">{{ sourceText }}</pre>
                    </details>
                </div>
            </section>

            <section class="rounded-lg border border-sidebar-border/70 bg-background dark:border-sidebar-border">
                <div class="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                    <h2 class="text-sm font-medium">Generated JSON Schema</h2>
                </div>
                <pre class="max-h-[32rem] overflow-auto p-4 text-xs leading-5"><code>{{ prettyJson }}</code></pre>
            </section>
        </div>
    </AppLayout>
</template>
