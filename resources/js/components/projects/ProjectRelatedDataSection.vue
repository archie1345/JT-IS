<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { FolderOpen, Plus, Upload } from 'lucide-vue-next';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import DocumentUploadPanel from '@/components/shared/DocumentUploadPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type {
    DocumentConnectionOption,
    ProjectDocumentGroup,
    ProjectDocumentGroupRecord,
    UploadedDocument,
} from '@/types/project';

const props = defineProps<{
    connectionOptions: DocumentConnectionOption[];
    documents: UploadedDocument[];
    groups: ProjectDocumentGroup[];
    isCreateMode: boolean;
    projectId: number | null;
}>();

const emit = defineEmits<{
    create: [group: ProjectDocumentGroup];
}>();

const isImportDialogOpen = ref(false);

const documentGroups = computed(() => props.groups ?? []);

const openDocumentRecord = (record: ProjectDocumentGroupRecord) => {
    router.get(record.url);
};

const openDocumentGroup = (group: ProjectDocumentGroup) => {
    router.get(group.listUrl);
};

const visibleGroupRecords = (group: ProjectDocumentGroup) =>
    group.records.slice(0, 3);

const hiddenGroupRecordCount = (group: ProjectDocumentGroup) =>
    Math.max(0, group.count - visibleGroupRecords(group).length);
</script>

<template>
    <EntityPageSection
        title="Data Terkait Proyek"
        description="Buka, tambah, dan kelola data proyek seperti RAB, RAP, progress, tagihan, biaya, dan pengajuan dana."
        :icon="FolderOpen"
    >
        <div
            v-if="isCreateMode"
            class="rounded-xl border border-dashed border-sidebar-border/70 bg-muted/20 p-4 text-sm text-muted-foreground"
        >
            Simpan proyek terlebih dahulu, lalu unggah dokumen di sini.
        </div>

        <div v-else class="min-w-0 space-y-4">
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-sidebar-border/70 bg-muted/10 p-3"
            >
                <div class="min-w-0">
                    <p class="text-sm font-medium text-foreground">
                        File pendukung & OCR
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Upload file proyek saat dibutuhkan.
                    </p>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    @click="isImportDialogOpen = true"
                >
                    <Upload class="size-4" />
                    Import Dokumen
                </Button>
            </div>

            <div class="grid min-w-0 gap-3 lg:grid-cols-2 2xl:grid-cols-3">
                <div
                    v-for="group in documentGroups"
                    :key="group.key"
                    class="flex min-h-0 min-w-0 flex-col gap-3 rounded-xl border border-sidebar-border/70 bg-muted/10 p-3"
                >
                    <div class="flex min-w-0 items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p
                                    class="truncate text-sm font-semibold text-foreground"
                                    :title="group.label"
                                >
                                    {{ group.label }}
                                </p>
                                <Badge variant="outline" class="shrink-0">
                                    {{ group.count }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ group.description }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-if="group.createKind"
                            type="button"
                            size="sm"
                            @click="emit('create', group)"
                        >
                            <Plus class="size-4" />
                            Tambah
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="openDocumentGroup(group)"
                        >
                            Lihat Semua
                        </Button>
                    </div>

                    <div class="min-w-0 space-y-2">
                        <button
                            v-for="record in visibleGroupRecords(group)"
                            :key="record.id"
                            type="button"
                            class="flex w-full min-w-0 items-start justify-between gap-3 rounded-lg border border-sidebar-border/70 bg-background px-3 py-2 text-left transition hover:bg-muted/40"
                            @click="openDocumentRecord(record)"
                        >
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-medium text-foreground"
                                    :title="record.title"
                                >
                                    {{ record.title }}
                                </p>
                                <p
                                    class="line-clamp-2 text-xs text-muted-foreground"
                                    :title="record.detail"
                                >
                                    {{ record.detail }}
                                </p>
                            </div>
                            <span
                                v-if="record.value"
                                class="shrink-0 text-xs font-medium text-foreground"
                            >
                                {{ record.value }}
                            </span>
                        </button>

                        <div
                            v-if="group.records.length === 0"
                            class="rounded-lg border border-dashed border-sidebar-border/70 bg-background/60 p-3 text-sm text-muted-foreground"
                        >
                            Belum ada data.
                        </div>

                        <button
                            v-if="hiddenGroupRecordCount(group) > 0"
                            type="button"
                            class="text-xs font-medium text-primary hover:underline"
                            @click="openDocumentGroup(group)"
                        >
                            +{{ hiddenGroupRecordCount(group) }} data lainnya
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:open="isImportDialogOpen">
            <DialogContent
                class="flex max-h-[calc(100dvh-2rem)] w-[calc(100vw-2rem)] !max-w-4xl flex-col overflow-hidden p-4 sm:p-6"
            >
                <DialogHeader class="shrink-0">
                    <DialogTitle>Import Dokumen</DialogTitle>
                    <DialogDescription>
                        Pilih lokasi dokumen, upload file, lalu jalankan OCR
                        bila tersedia.
                    </DialogDescription>
                </DialogHeader>

                <div class="min-h-0 flex-1 overflow-y-auto pr-1">
                    <DocumentUploadPanel
                        :project-id="projectId"
                        component-type="project"
                        :connection-options="connectionOptions"
                        :documents="documents"
                        title="Dokumen & OCR"
                        description="Unggah file proyek, jalankan OCR bila tersedia, lalu review hasilnya."
                        empty-text="Belum ada dokumen proyek."
                    />
                </div>
            </DialogContent>
        </Dialog>
    </EntityPageSection>
</template>
