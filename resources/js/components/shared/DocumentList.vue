<script setup lang="ts">
import { computed, ref, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { FileText, LoaderCircle, RotateCcw, Trash2, Upload } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import TextPreview from '@/components/shared/TextPreview.vue';
import type { UploadedDocument } from '@/types/project';
import type { ConnectionOption, ProjectOption } from '@/types/options';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import DocumentUploadPanel from '@/components/shared/DocumentUploadPanel.vue';

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
        isReadingFile?: boolean;
        storedOcrDocumentId?: number | null;
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
        isReadingFile: false,
        storedOcrDocumentId: null,
    },
);

const isUploadOpen = ref(false);
const uploadPanelRef = ref<{ 
    runStoredDocumentOcr: (doc: UploadedDocument) => void;
    clearSelectedFiles: () => void; 
} | null>(null);
const ocrTargetDocument = ref<UploadedDocument | null>(null);

const openImportDialog = () => {
    ocrTargetDocument.value = null; // Reset target
    isUploadOpen.value = true;
    
    nextTick(() => {
        uploadPanelRef.value?.clearSelectedFiles();
    });
};

const emit = defineEmits<{
    (e: 'run-ocr', document: UploadedDocument): void;
}>();

const documentSummary = (document: UploadedDocument) => {
    return [
        document.projectName,
        document.createdAt ?? 'diunggah',
        document.size ? `${Math.round(document.size / 1024)} KB` : 'file',
    ]
        .filter(Boolean)
        .join(' / ');
};

const runStoredDocumentOcr = async (document: UploadedDocument) => {
    ocrTargetDocument.value = document; // Set target document
    isUploadOpen.value = true;
    
    await nextTick();
    uploadPanelRef.value?.runStoredDocumentOcr(document);
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
    <div
        class="min-w-0 rounded-lg border border-sidebar-border/70 bg-background p-3 shadow-xs sm:p-4 dark:border-sidebar-border"
    >
        <div class="flex min-w-0 items-center gap-2 justify-between">
            <div class="flex items-center gap-2">
                <FileText class="size-4 shrink-0 text-muted-foreground" />
                <h3 class="text-sm font-medium">Dokumen Proyek</h3>
            </div>
            <Button
                type="button"
                variant="outline"
                @click="openImportDialog"
            >
                <Upload class="size-4" />
                Import Dokumen
            </Button>
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
                            props.isReadingFile &&
                            props.storedOcrDocumentId === document.id
                        "
                        @click="runStoredDocumentOcr(document)"
                    >
                        <LoaderCircle
                            v-if="
                                props.isReadingFile &&
                                props.storedOcrDocumentId === document.id
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

        <Dialog v-model:open="isUploadOpen">
            <DialogContent
                class="flex max-h-[calc(100dvh-2rem)] w-[calc(100vw-2rem)] !max-w-4xl flex-col overflow-hidden p-4 sm:p-6"
            >
                <DialogHeader class="shrink-0">
                    <DialogTitle>
                        {{ ocrTargetDocument ? 'Proses Ulang OCR' : 'Import Dokumen' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ 
                            ocrTargetDocument 
                                ? `Menjalankan ulang ekstraksi data teks untuk file: ${ocrTargetDocument.originalName}`
                                : 'Pilih lokasi dokumen, upload file, lalu jalankan OCR bila tersedia.' 
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="min-h-0 flex-1 overflow-y-auto pr-1">
                    <DocumentUploadPanel
                        ref="uploadPanelRef"
                        :project-id="projectId"
                        :component-type="componentType"
                        :component-id="componentId"
                        :connection-options="connectionOptions"
                        :documents="documents"
                        title="Dokumen & OCR"
                        description="Unggah file proyek, jalankan OCR bila tersedia, lalu review hasilnya."
                        empty-text="Belum ada dokumen proyek."
                        :project-options="projectOptions"
                    />
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>