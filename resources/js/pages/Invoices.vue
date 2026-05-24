<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { FileText, Palette, Printer } from 'lucide-vue-next';
import CrudPrototypePage from '@/components/prototype/CrudPrototypePage.vue';
import type { SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';
import type { UploadedDocument } from '@/types/project';

type Option = {
    value: number;
    label: string;
    hint?: null | string;
};

const props = defineProps<{
    records: Record<string, null | number | string>[];
    projectOptions: Option[];
    uploadedDocuments: UploadedDocument[];
    pagination: {
        currentPage: number;
        lastPage: number;
        maxPerPage?: number;
        perPage: number;
        perPageOptions?: number[];
        total: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Billing', href: '/invoices' }];
const selectedInvoiceId = ref(
    props.records.length > 0 ? String(props.records[0].id) : '',
);
const isInvoiceModalOpen = ref(false);
const isTemplateModalOpen = ref(false);

const template = reactive({
    title: 'INVOICE',
    accentColor: '#0f766e',
    textColor: '#111827',
    paperColor: '#ffffff',
    borderColor: '#d1d5db',
    footerText: 'Thank you for your business.',
    bankDetails: 'Bank: \nAccount Name: \nAccount No: ',
    notes: 'Please make payment according to the bank information below.',
    showBankDetails: true,
    showNotes: true,
    showSignature: true,
});

const uploadConnectionOptions = computed(() =>
    props.records.map((record) => ({
        value: `invoice:${record.id}`,
        label: `Invoice #${record.id}`,
        hint: String(record.project_name ?? ''),
        componentType: 'invoice',
        componentId: Number(record.id),
        projectId: Number(record.project_id),
    })),
);

const columns = [
    { key: 'id', label: 'Id' },
    { key: 'project_name', label: 'Project' },
    { key: 'client_name', label: 'Client' },
    { key: 'invoice_number', label: 'Invoice No.' },
    { key: 'amount', label: 'Amount' },
    { key: 'tax_amount', label: 'Tax' },
    { key: 'invoice_date', label: 'Invoice Date' },
    { key: 'due_date', label: 'Due Date' },
    { key: 'status', label: 'Status' },
] satisfies SpreadsheetColumn[];

const fields = [
    {
        name: 'project_id',
        label: 'Project',
        type: 'select',
        options: props.projectOptions,
        required: true,
    },
    {
        name: 'invoice_number',
        label: 'Invoice Number',
        type: 'text',
        placeholder: 'Invoice or billing reference number',
    },
    { name: 'amount', label: 'Amount', type: 'number', min: 0, step: '0.01' },
    {
        name: 'tax_amount',
        label: 'Tax Amount',
        type: 'number',
        min: 0,
        step: '0.01',
    },
    { name: 'invoice_date', label: 'Invoice Date', type: 'date' },
    { name: 'due_date', label: 'Due Date', type: 'date' },
    {
        name: 'status',
        label: 'Status',
        type: 'select',
        options: [
            { value: 'pending', label: 'Pending' },
            { value: 'paid', label: 'Paid' },
            { value: 'overdue', label: 'Overdue' },
        ],
    },
    {
        name: 'description',
        label: 'Description',
        type: 'textarea',
        placeholder: 'Billing notes or scope',
    },
] as const;

const selectedInvoice = computed(
    () =>
        props.records.find(
            (record) => String(record.id) === selectedInvoiceId.value,
        ) ?? props.records[0],
);

const formatCurrency = (value: null | number | string | undefined) =>
    value === null || value === undefined || value === ''
        ? '-'
        : new Intl.NumberFormat('id-ID', {
              style: 'currency',
              currency: 'IDR',
              maximumFractionDigits: 0,
          }).format(Number(value));

const invoiceAmount = computed(() =>
    Number(selectedInvoice.value?.amount ?? 0),
);
const invoiceTax = computed(() =>
    Number(selectedInvoice.value?.tax_amount ?? 0),
);
const invoiceTotal = computed(() => invoiceAmount.value + invoiceTax.value);
const invoiceStyle = computed(() => ({
    '--invoice-accent': template.accentColor,
    '--invoice-text': template.textColor,
    '--invoice-paper': template.paperColor,
    '--invoice-border': template.borderColor,
}));

const openInvoiceModal = () => {
    if (!selectedInvoiceId.value && props.records[0]?.id) {
        selectedInvoiceId.value = String(props.records[0].id);
    }

    isInvoiceModalOpen.value = true;
};

const printInvoice = () => {
    window.print();
};
</script>

<template>
    <CrudPrototypePage
        head-title="Billing"
        title="Billing"
        description="Simple invoice CRUD prototype using the existing invoices table."
        :breadcrumbs="breadcrumbs"
        :rows="props.records"
        :columns="columns"
        :fields="fields"
        create-url="/invoices"
        update-url-base="/invoices"
        delete-url-base="/invoices"
        detail-url-base="/invoices"
        upload-component-type="invoice"
        :project-options="props.projectOptions"
        :uploaded-documents="props.uploadedDocuments"
        :upload-connection-options="uploadConnectionOptions"
        :pagination="props.pagination"
        create-label="New Invoice"
        :note="`Showing ${props.pagination.total} invoice record(s)`"
    >
        <template #toolbar-actions>
            <div
                class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row"
                data-spreadsheet-table-root
            >
                <Button
                    variant="outline"
                    class="w-full text-sm sm:w-auto"
                    :disabled="props.records.length === 0"
                    @click="openInvoiceModal"
                >
                    <FileText class="size-4" />
                    Make Invoice
                </Button>
                <Button
                    variant="outline"
                    class="w-full text-sm sm:w-auto"
                    @click="isTemplateModalOpen = true"
                >
                    <Palette class="size-4" />
                    Template
                </Button>
            </div>
        </template>

        <template #cell-amount="{ value }">
            {{ formatCurrency(value) }}
        </template>
        <template #cell-tax_amount="{ value }">
            {{ formatCurrency(value) }}
        </template>
    </CrudPrototypePage>

    <Dialog v-model:open="isInvoiceModalOpen">
        <DialogContent
            class="max-h-[calc(100vh-2rem)] w-[calc(100vw-2rem)] overflow-y-auto sm:max-w-5xl"
        >
            <DialogHeader class="no-print">
                <DialogTitle>Make Invoice</DialogTitle>
                <DialogDescription>
                    Pick a billing record, preview the template, then print or
                    save it as PDF.
                </DialogDescription>
            </DialogHeader>

            <div class="no-print grid gap-4 sm:grid-cols-[1fr_auto_auto]">
                <div class="space-y-1.5">
                    <Label for="billing_invoice_source">Billing Record</Label>
                    <select
                        id="billing_invoice_source"
                        v-model="selectedInvoiceId"
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option
                            v-for="record in props.records"
                            :key="String(record.id)"
                            :value="String(record.id)"
                        >
                            {{
                                record.invoice_number || `Invoice #${record.id}`
                            }}
                            - {{ record.project_name || 'Untitled project' }}
                        </option>
                    </select>
                </div>
                <Button
                    variant="outline"
                    class="self-end"
                    @click="isTemplateModalOpen = true"
                >
                    <Palette class="size-4" />
                    Change Template
                </Button>
                <Button class="self-end" @click="printInvoice">
                    <Printer class="size-4" />
                    Create PDF
                </Button>
            </div>

            <section
                v-if="selectedInvoice"
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
                                <p class="text-sm tracking-[0.2em] uppercase">
                                    PT. Jasa Tirta Energi
                                </p>
                                <h1 class="mt-4 text-4xl font-semibold">
                                    {{ template.title }}
                                </h1>
                            </div>
                            <div class="text-right text-sm">
                                <p class="font-medium">
                                    {{
                                        selectedInvoice.invoice_number ||
                                        `Invoice #${selectedInvoice.id}`
                                    }}
                                </p>
                                <p class="mt-1">
                                    Date:
                                    {{ selectedInvoice.invoice_date || '-' }}
                                </p>
                                <p>
                                    Due: {{ selectedInvoice.due_date || '-' }}
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
                                    {{ selectedInvoice.client_name || '-' }}
                                </p>
                                <p class="mt-1 opacity-75">
                                    {{
                                        selectedInvoice.project_name ||
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
                                        selectedInvoice.description ||
                                        'Project billing'
                                    }}
                                </p>
                                <p class="mt-2 capitalize opacity-75">
                                    Status:
                                    {{ selectedInvoice.status || 'pending' }}
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
                                                selectedInvoice.description ||
                                                selectedInvoice.project_name ||
                                                'Project billing'
                                            }}
                                        </td>
                                        <td class="px-4 py-3 text-right">1</td>
                                        <td class="px-4 py-3 text-right">
                                            {{
                                                formatCurrency(
                                                    selectedInvoice.amount,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-[1fr_18rem]">
                            <div class="space-y-4">
                                <div v-if="template.showBankDetails">
                                    <p class="font-medium">Bank Details</p>
                                    <p
                                        class="mt-1 whitespace-pre-line opacity-75"
                                    >
                                        {{ template.bankDetails }}
                                    </p>
                                </div>
                                <div v-if="template.showNotes">
                                    <p class="font-medium">Notes</p>
                                    <p
                                        class="mt-1 whitespace-pre-line opacity-75"
                                    >
                                        {{ template.notes }}
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
                                        borderColor: 'var(--invoice-border)',
                                    }"
                                >
                                    <span>Total</span>
                                    <span>{{
                                        formatCurrency(invoiceTotal)
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="template.showSignature"
                            class="mt-auto flex justify-end pt-8"
                        >
                            <div class="w-48 text-center">
                                <div
                                    class="mb-16 border-t"
                                    :style="{
                                        borderColor: 'var(--invoice-border)',
                                    }"
                                ></div>
                                <p class="font-medium">Authorized Signature</p>
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
                        {{ template.footerText }}
                    </div>
                </div>
            </section>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="isTemplateModalOpen">
        <DialogContent
            class="max-h-[calc(100vh-2rem)] w-[calc(100vw-2rem)] overflow-y-auto sm:max-w-2xl"
        >
            <DialogHeader>
                <DialogTitle>Invoice Template</DialogTitle>
                <DialogDescription>
                    Change the printable invoice style used from Billing.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-2 sm:grid-cols-2">
                <div class="space-y-1.5">
                    <Label for="invoice_template_title">Title</Label>
                    <Input
                        id="invoice_template_title"
                        v-model="template.title"
                    />
                </div>
                <div class="space-y-1.5">
                    <Label for="invoice_footer">Footer Text</Label>
                    <Input id="invoice_footer" v-model="template.footerText" />
                </div>
                <div class="space-y-1.5">
                    <Label for="invoice_accent">Accent Color</Label>
                    <Input
                        id="invoice_accent"
                        v-model="template.accentColor"
                        type="color"
                    />
                </div>
                <div class="space-y-1.5">
                    <Label for="invoice_text">Text Color</Label>
                    <Input
                        id="invoice_text"
                        v-model="template.textColor"
                        type="color"
                    />
                </div>
                <div class="space-y-1.5">
                    <Label for="invoice_paper">Paper Color</Label>
                    <Input
                        id="invoice_paper"
                        v-model="template.paperColor"
                        type="color"
                    />
                </div>
                <div class="space-y-1.5">
                    <Label for="invoice_border">Border Color</Label>
                    <Input
                        id="invoice_border"
                        v-model="template.borderColor"
                        type="color"
                    />
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="template.showBankDetails" type="checkbox" />
                    Show bank details
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="template.showNotes" type="checkbox" />
                    Show notes
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="template.showSignature" type="checkbox" />
                    Show signature
                </label>
                <div class="space-y-1.5 sm:col-span-2">
                    <Label for="invoice_bank">Bank Details</Label>
                    <textarea
                        id="invoice_bank"
                        v-model="template.bankDetails"
                        class="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    ></textarea>
                </div>
                <div class="space-y-1.5 sm:col-span-2">
                    <Label for="invoice_notes">Notes</Label>
                    <textarea
                        id="invoice_notes"
                        v-model="template.notes"
                        class="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    ></textarea>
                </div>
            </div>

            <DialogFooter>
                <Button @click="isTemplateModalOpen = false">
                    Apply Template
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
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
