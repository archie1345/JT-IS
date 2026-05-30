<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FileText, Save } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import DocumentUploadPanel from '@/components/shared/DocumentUploadPanel.vue';
import RecordFieldInput from '@/components/prototype/RecordFieldInput.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import type { UploadedDocument } from '@/types/project';

type Option = {
    value: number | string;
    label: string;
    hint?: null | string;
};

type Field = {
    name: string;
    label: string;
    type: 'date' | 'number' | 'select' | 'text' | 'textarea';
    placeholder?: string;
    required?: boolean;
    min?: number;
    max?: number;
    step?: string;
    options?: Option[];
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
                    Back
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
                            Make PDF
                        </Button>
                        <Button :disabled="form.processing" @click="submit">
                            <Save class="mr-2 size-4" />
                            Save Changes
                        </Button>
                    </div>
                </div>
            </section>

            <section
                class="grid min-w-0 gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,24rem)]"
            >
                <EntityPageSection
                    title="Record Fields"
                    description="Edit the fields captured from the source document."
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
                                Save Changes
                            </Button>
                        </div>
                    </form>
                </EntityPageSection>

                <EntityPageSection
                    v-if="props.upload"
                    title="Attached Files"
                    description="Documents linked to this record."
                >
                    <DocumentUploadPanel
                        :project-id="props.upload.projectId"
                        :component-type="props.upload.componentType"
                        :component-id="props.upload.componentId"
                        :documents="props.upload.documents"
                        title="Record files"
                        description="Upload source files and supporting evidence for this record."
                    />
                </EntityPageSection>
            </section>
        </div>
    </AppLayout>
</template>
