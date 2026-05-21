<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FileText, Printer, Save } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import ProjectDocumentUploadPanel from '@/components/ProjectDocumentUploadPanel.vue';
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
const invoiceStyle = {
    '--invoice-accent': '#0f766e',
    '--invoice-text': '#111827',
    '--invoice-paper': '#ffffff',
    '--invoice-border': '#d1d5db',
};

const formatCurrency = (value: null | number | string | undefined) =>
    value === null || value === undefined || value === ''
        ? '-'
        : new Intl.NumberFormat('id-ID', {
              style: 'currency',
              currency: 'IDR',
              maximumFractionDigits: 0,
          }).format(Number(value));

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

                <section
                    class="invoice-print-area overflow-x-auto rounded-lg bg-muted/30 p-3"
                >
                    <div
                        class="invoice-sheet mx-auto flex min-h-[297mm] w-[210mm] flex-col bg-white shadow-lg"
                        :style="invoiceStyle"
                    >
                        <div
                            class="px-10 py-8"
                            :style="{
                                background: 'var(--invoice-accent)',
                                color: 'white',
                            }"
                        >
                            <div class="flex items-start justify-between gap-8">
                                <div>
                                    <p
                                        class="text-sm tracking-[0.2em] uppercase"
                                    >
                                        PT. Jasa Tirta Energi
                                    </p>
                                    <h1 class="mt-4 text-4xl font-semibold">
                                        INVOICE
                                    </h1>
                                </div>
                                <div class="text-right text-sm">
                                    <p class="font-medium">
                                        {{
                                            invoiceRecord.invoice_number ||
                                            `Invoice #${invoiceRecord.id}`
                                        }}
                                    </p>
                                    <p class="mt-1">
                                        Date:
                                        {{ invoiceRecord.invoice_date || '-' }}
                                    </p>
                                    <p>
                                        Due:
                                        {{ invoiceRecord.due_date || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-1 flex-col gap-8 px-10 py-8 text-sm"
                            :style="{
                                color: 'var(--invoice-text)',
                                background: 'var(--invoice-paper)',
                            }"
                        >
                            <div class="grid gap-6 sm:grid-cols-2">
                                <div>
                                    <p
                                        class="text-xs font-medium uppercase opacity-60"
                                    >
                                        Bill To
                                    </p>
                                    <p class="mt-2 text-lg font-semibold">
                                        {{ invoiceRecord.client_name || '-' }}
                                    </p>
                                    <p class="mt-1 opacity-75">
                                        {{
                                            invoiceRecord.project_name ||
                                            'Project billing'
                                        }}
                                    </p>
                                </div>
                                <div
                                    class="rounded border p-4"
                                    :style="{
                                        borderColor: 'var(--invoice-border)',
                                    }"
                                >
                                    <p class="font-medium">Billing Summary</p>
                                    <p class="mt-2 opacity-75">
                                        {{
                                            invoiceRecord.description ||
                                            'Project billing'
                                        }}
                                    </p>
                                    <p class="mt-2 capitalize opacity-75">
                                        Status:
                                        {{ invoiceRecord.status || 'pending' }}
                                    </p>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded border">
                                <table class="w-full border-collapse">
                                    <thead
                                        :style="{
                                            background: 'var(--invoice-accent)',
                                            color: 'white',
                                        }"
                                    >
                                        <tr>
                                            <th class="px-4 py-3 text-left">
                                                Description
                                            </th>
                                            <th class="px-4 py-3 text-right">
                                                Qty
                                            </th>
                                            <th class="px-4 py-3 text-right">
                                                Amount
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="px-4 py-3">
                                                {{
                                                    invoiceRecord.description ||
                                                    invoiceRecord.project_name ||
                                                    'Project billing'
                                                }}
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                1
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                {{
                                                    formatCurrency(
                                                        invoiceRecord.amount,
                                                    )
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="grid gap-6 sm:grid-cols-[1fr_18rem]">
                                <div class="space-y-4">
                                    <div>
                                        <p class="font-medium">Bank Details</p>
                                        <p
                                            class="mt-1 whitespace-pre-line opacity-75"
                                        >
                                            Bank:
                                            <br />
                                            Account Name:
                                            <br />
                                            Account No:
                                        </p>
                                    </div>
                                    <div>
                                        <p class="font-medium">Notes</p>
                                        <p class="mt-1 opacity-75">
                                            Please make payment according to the
                                            bank information above.
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex justify-between gap-4">
                                        <span>Subtotal</span>
                                        <span>{{
                                            formatCurrency(invoiceAmount)
                                        }}</span>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <span>Tax</span>
                                        <span>{{
                                            formatCurrency(invoiceTax)
                                        }}</span>
                                    </div>
                                    <div
                                        class="mt-3 flex justify-between gap-4 border-t pt-3 text-base font-semibold"
                                        :style="{
                                            borderColor:
                                                'var(--invoice-border)',
                                        }"
                                    >
                                        <span>Total</span>
                                        <span>{{
                                            formatCurrency(invoiceTotal)
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto flex justify-end pt-8">
                                <div class="w-48 text-center">
                                    <div
                                        class="mb-16 border-t"
                                        :style="{
                                            borderColor:
                                                'var(--invoice-border)',
                                        }"
                                    ></div>
                                    <p class="font-medium">
                                        Authorized Signature
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="border-t px-10 py-4 text-center text-xs opacity-70"
                            :style="{
                                borderColor: 'var(--invoice-border)',
                                color: 'var(--invoice-text)',
                                background: 'var(--invoice-paper)',
                            }"
                        >
                            Thank you for your business.
                        </div>
                    </div>
                </section>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<style>
.invoice-sheet :where(div, section):has(> table) {
    overflow-x: visible !important;
}

.invoice-sheet :where(div, section):has(> table) > table {
    width: 100% !important;
    min-width: 100%;
}

@media print {
    @page {
        size: A4;
        margin: 0;
    }

    body * {
        visibility: hidden;
    }

    .invoice-print-area,
    .invoice-print-area * {
        visibility: visible;
    }

    .invoice-print-area {
        position: absolute;
        inset: 0;
        overflow: visible !important;
        background: white;
    }

    .invoice-sheet {
        width: 210mm;
        min-height: 297mm;
        box-shadow: none !important;
    }

    .no-print {
        display: none !important;
    }
}
</style>
