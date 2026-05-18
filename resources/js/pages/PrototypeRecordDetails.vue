<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import ProjectDocumentUploadPanel from '@/components/ProjectDocumentUploadPanel.vue';
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

const backToList = () => {
    router.get(props.indexUrl);
};

const refreshPage = () => {
    router.reload({ preserveScroll: true });
};

const submit = () => {
    form.patch(props.updateUrl, {
        preserveScroll: true,
        onSuccess: refreshPage,
    });
};
</script>

<template>
    <Head :title="props.title" />

    <AppLayout :breadcrumbs="props.breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 rounded-xl p-4">
            <section
                class="rounded-2xl border border-sidebar-border/70 bg-background p-5 shadow-sm"
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
                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <h1 class="text-3xl font-semibold tracking-tight">
                            {{ props.title }}
                        </h1>
                        <p
                            v-if="props.subtitle"
                            class="mt-2 text-sm text-muted-foreground"
                        >
                            {{ props.subtitle }}
                        </p>
                    </div>
                    <Button :disabled="form.processing" @click="submit">
                        <Save class="mr-2 size-4" />
                        Save Changes
                    </Button>
                </div>
            </section>

            <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <EntityPageSection
                    title="Record Fields"
                    description="Edit the fields captured from the source document."
                >
                    <form
                        class="grid gap-4 sm:grid-cols-2"
                        @submit.prevent="submit"
                    >
                        <RecordFieldInput
                            v-for="field in props.fields"
                            :key="field.name"
                            v-model="form[field.name]"
                            :field="field"
                            :error="form.errors[field.name]"
                        />

                        <div class="flex justify-end sm:col-span-2">
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
                    <ProjectDocumentUploadPanel
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
