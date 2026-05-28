<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FileText, Printer, Save } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import InvoicePrintPreview from '@/components/invoice/InvoicePrintPreview.vue';
import DocumentUploadPanel from '@/components/shared/DocumentUploadPanel.vue';
import RecordFieldInput from '@/components/prototype/RecordFieldInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
const isPdfModalOpen = ref(false);
const isInvoiceRecord = computed(
    () => props.upload?.componentType === 'invoice',
);
const invoiceRecord = computed(() => ({
    id: props.record.id,
    project_name: props.record.project_name,
    client_name: props.record.client_name,
    invoice_number: form.invoice_number || props.record.invoice_number,
    amount: form.amount || props.record.amount,
    tax_amount: form.tax_amount || props.record.tax_amount,
    invoice_date: form.invoice_date || props.record.invoice_date,
    due_date: form.due_date || props.record.due_date,
    status: form.status || props.record.status,
    description: form.description || props.record.description,
}));
const invoiceAmount = computed(() => Number(invoiceRecord.value.amount ?? 0));
const invoiceTax = computed(() => Number(invoiceRecord.value.tax_amount ?? 0));
const invoiceTotal = computed(() => invoiceAmount.value + invoiceTax.value);
const invoiceLineItems = computed(() => [
    {
        description:
            invoiceRecord.value.description ||
            invoiceRecord.value.project_name ||
            'Project billing',
        projectName: invoiceRecord.value.project_name,
        quantity: 1,
        unitPrice: invoiceRecord.value.amount,
        totalPrice: invoiceRecord.value.amount,
    },
]);

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

const printInvoice = () => {
    window.print();
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
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <Button
                            v-if="isInvoiceRecord"
                            variant="outline"
                            @click="isPdfModalOpen = true"
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

        <Dialog v-model:open="isPdfModalOpen">
            <DialogContent
                class="max-h-[calc(100vh-2rem)] w-[calc(100vw-2rem)] overflow-y-auto sm:max-w-5xl"
            >
                <DialogHeader class="no-print">
                    <DialogTitle>Invoice PDF</DialogTitle>
                    <DialogDescription>
                        Preview this billing record, then print or save it as
                        PDF.
                    </DialogDescription>
                </DialogHeader>

                <div class="no-print flex justify-end">
                    <Button @click="printInvoice">
                        <Printer class="size-4" />
                        Create PDF
                    </Button>
                </div>

                <InvoicePrintPreview
                    :bill-to="invoiceRecord.client_name"
                    :description="invoiceRecord.description"
                    :due-date="invoiceRecord.due_date"
                    :invoice-date="invoiceRecord.invoice_date"
                    :invoice-number="
                        String(
                            invoiceRecord.invoice_number ||
                                `Invoice #${invoiceRecord.id}`,
                        )
                    "
                    :line-items="invoiceLineItems"
                    :project-name="invoiceRecord.project_name"
                    :status="invoiceRecord.status"
                    :subtotal="invoiceAmount"
                    :tax="invoiceTax"
                    :total="invoiceTotal"
                    variant="summary"
                />
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
