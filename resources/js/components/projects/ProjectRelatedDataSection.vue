<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { FolderOpen, Plus, Upload, RefreshCw, Trash2 } from 'lucide-vue-next';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type {
    DocumentConnectionOption,
    ProjectDocumentGroup,
    ProjectDocumentGroupRecord,
    UploadedDocument,
} from '@/types/project';
import DocumentList from '../shared/DocumentList.vue';

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

const refreshGroups = () => {
    router.reload({
        only: ['groups']
    });
};

const destroyRecord = (record: ProjectDocumentGroupRecord) => {
    if (!window.confirm(`Hapus data ${record.title}?`)) {
        return;
    }

    router.delete(record.url, {
        preserveScroll: true,
        onSuccess: () => {
            refreshGroups();
        },
    });
};
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
            <DocumentList
                :project-id="props.projectId"
                component-type="project"
                :documents="props.documents"
                :project-options="[]"
                :connection-options="props.connectionOptions"
                @upload-success="refreshGroups"
            />

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
                        <div
                            v-for="record in visibleGroupRecords(group)"
                            :key="record.id"
                            class="group/record flex w-full min-w-0 items-start justify-between gap-2 rounded-lg border border-sidebar-border/70 bg-background px-3 py-2 transition hover:bg-muted/40"
                        >
                            <button
                                type="button"
                                class="flex min-w-0 flex-1 items-start justify-between gap-3 text-left"
                                @click="openDocumentRecord(record)"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-medium text-foreground hover:underline"
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

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                class="shrink-0 text-destructive"
                                @click="destroyRecord(record)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>

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
    </EntityPageSection>
</template>