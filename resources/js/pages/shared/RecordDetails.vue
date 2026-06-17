<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FileText, Save } from 'lucide-vue-next';
import { computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import RecordFieldInput from '@/components/prototype/RecordFieldInput.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import type { UploadedDocument } from '@/types/project';
import type { ProjectOption, ConnectionOption } from '@/types/options';
import DocumentList from '@/components/shared/DocumentList.vue';
import { usePage } from '@inertiajs/vue3';

type Field = {
    name: string;
    label: string;
    type: 'date' | 'number' | 'select' | 'text' | 'textarea';
    placeholder?: string;
    required?: boolean;
    min?: number;
    max?: number;
    step?: string;
    options?: ProjectOption[];
};

type RecordValue = null | number | string;

const props = defineProps<{
    title: string;
    subtitle?: null | string;
    indexUrl: string;
    updateUrl: string;
    breadcrumbs: BreadcrumbItem[];
    fields: Field[];
    record: Record<string, RecordValue>;
    projectOptions: readonly ProjectOption[];
    connectionOptions: readonly ConnectionOption[];
    upload?: {
        componentType: string;
        componentId: number;
        projectId?: null | number;
        documents: UploadedDocument[];
    };
}>();

const form = useForm<Record<string, number | string>>(
    Object.fromEntries(
        props.fields.map((field) => [
            field.name,
            props.record[field.name] ?? '',
        ]),
    ),
);

watch(
    () => props.record,
    (newRecord) => {
        const updatedData = Object.fromEntries(
            props.fields.map((field) => [
                field.name,
                newRecord[field.name] ?? '',
            ])
        );
        form.defaults(updatedData);
        form.reset();
    },
    { deep: true }
);

const page = usePage();

const isPipelinePage = computed(() => page.url.startsWith('/pipeline'));

const isInvoiceRecord = computed(
    () => props.upload?.componentType === 'invoice',
);

const backToList = () => {
    router.get(props.indexUrl);
};

const refreshPage = () => {
    router.reload();
};

const submit = () => {
    form.patch(props.updateUrl, {
        preserveScroll: true,
        onSuccess: refreshPage,
    });
};

const openInvoicePreview = () => {
    window.open(`/invoices/${props.record.id}/preview`, '_blank', 'noopener');
};
</script>

<template>
    <Head :title="props.title" />

    <AppLayout :breadcrumbs="props.breadcrumbs">
        <div
            class="flex min-w-0 flex-1 flex-col gap-3 rounded-xl p-2 sm:gap-4 sm:p-4"
        >
            <section
                class="min-w-0 overflow-hidden rounded-xl border border-sidebar-border/70 bg-background p-3 shadow-sm sm:rounded-2xl sm:p-5"
            >
                <Button
                    variant="ghost"
                    class="mb-3 pl-0 text-muted-foreground"
                    @click="backToList"
                >
                    <ArrowLeft class="mr-2 size-4" />
                    Kembali
                </Button>
                <div
                    class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="min-w-0">
                        <h1
                            class="text-2xl font-semibold tracking-tight break-words sm:text-3xl"
                        >
                            {{ props.title }}
                        </h1>
                        <p
                            v-if="props.subtitle"
                            class="mt-2 text-xs break-words text-muted-foreground sm:text-sm"
                        >
                            {{ props.subtitle }}
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                        <Button
                            v-if="isInvoiceRecord"
                            variant="outline"
                            @click="openInvoicePreview"
                        >
                            <FileText class="mr-2 size-4" />
                            Buat PDF
                        </Button>
                        <Button :disabled="form.processing" @click="submit">
                            <Save class="mr-2 size-4" />
                            Simpan Perubahan
                        </Button>
                    </div>
                </div>
            </section>

            <section
                class="grid min-w-0 gap-4"
            >
                <EntityPageSection
                    title="Field Data"
                    description="Edit field yang terbaca dari dokumen sumber."
                >
                    <form
                        class="grid min-w-0 gap-4 sm:grid-cols-2"
                        @submit.prevent="submit"
                    >
                        <RecordFieldInput
                            v-for="field in props.fields"
                            :key="field.name"
                            v-model="form[field.name]"
                            :field="field"
                            :error="form.errors[field.name]"
                        />

                        <div class="flex min-w-0 justify-end sm:col-span-2">
                            <Button type="submit" :disabled="form.processing">
                                <Save class="mr-2 size-4" />
                                Simpan Perubahan
                            </Button>
                        </div>
                    </form>
                </EntityPageSection>

                <EntityPageSection
                    v-if="props.upload && !isPipelinePage"
                    title="File Terlampir"
                    description="Dokumen yang terhubung ke data ini."
                >
                    <DocumentList
                        :project-id="props.upload.projectId"
                        :component-type="props.upload.componentType"
                        :component-id="props.upload.componentId"
                        :documents="props.upload.documents"
                        title="File Data"
                        description="Upload file sumber dan bukti pendukung untuk data ini."
                    />
                </EntityPageSection>
            </section>
        </div>
    </AppLayout>
</template>