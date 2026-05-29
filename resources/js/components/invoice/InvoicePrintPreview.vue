<script setup lang="ts">
import { computed } from 'vue';
import { formatCurrency } from '@/lib/formatters';

type InvoiceValue = null | number | string;

export type InvoicePrintLineItem = {
    id?: number | string;
    category?: InvoiceValue;
    description?: InvoiceValue;
    projectName?: InvoiceValue;
    quantity?: InvoiceValue;
    unit?: InvoiceValue;
    unitPrice?: InvoiceValue;
    totalPrice?: InvoiceValue;
};

export type InvoicePrintTemplate = {
    title?: string;
    accentColor?: string;
    textColor?: string;
    paperColor?: string;
    borderColor?: string;
    footerText?: string;
    bankDetails?: string;
    notes?: string;
    showBankDetails?: boolean;
    showNotes?: boolean;
    showSignature?: boolean;
};

const props = withDefaults(
    defineProps<{
        billTo?: InvoiceValue;
        description?: InvoiceValue;
        dueDate?: InvoiceValue;
        invoiceDate?: InvoiceValue;
        invoiceNumber: string;
        lineItems?: InvoicePrintLineItem[];
        projectName?: InvoiceValue;
        status?: InvoiceValue;
        subtotal: number;
        tax?: number;
        template?: InvoicePrintTemplate;
        total: number;
        variant?: 'detail' | 'summary';
    }>(),
    {
        billTo: null,
        description: null,
        dueDate: null,
        invoiceDate: null,
        lineItems: () => [],
        projectName: null,
        status: 'pending',
        tax: 0,
        template: () => ({}),
        variant: 'detail',
    },
);

const template = computed(() => ({
    title: 'INVOICE',
    accentColor: '#0f766e',
    textColor: '#111827',
    paperColor: '#ffffff',
    borderColor: '#d1d5db',
    footerText: '',
    bankDetails:
        'Bank Mandiri\nAccount Name: PT. Jasa Tirta Energi\nAccount No: 000-000-0000',
    notes: 'Please include the invoice number on the payment reference. Thank you for your business.',
    showBankDetails: true,
    showNotes: true,
    showSignature: true,
    ...props.template,
}));

const invoiceStyle = computed(() => ({
    '--invoice-accent': template.value.accentColor,
    '--invoice-text': template.value.textColor,
    '--invoice-paper': template.value.paperColor,
    '--invoice-border': template.value.borderColor,
}));
</script>

<template>
    <section
        class="invoice-print-area overflow-x-auto rounded-lg bg-muted/30 p-3"
    >
        <div
            class="invoice-sheet mx-auto flex min-h-[297mm] w-[210mm] flex-col bg-white text-[#111827] shadow-lg"
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
                    <div class="flex items-start gap-4">
                        <div
                            class="flex size-16 shrink-0 items-center justify-center rounded-lg bg-white text-2xl font-bold"
                            :style="{ color: 'var(--invoice-accent)' }"
                        >
                            JTE
                        </div>
                        <div>
                            <p class="text-sm tracking-[0.2em] uppercase">
                                PT. Jasa Tirta Energi
                            </p>
                            <h1 class="mt-3 text-4xl font-semibold">
                                {{ template.title }}
                            </h1>
                            <p
                                class="mt-3 max-w-sm text-xs leading-relaxed text-white/80"
                            >
                                Jl. Surabaya No. 2A, Malang, Indonesia<br />
                                finance@jasatirtaenergi.co.id | +62 341 000 000
                            </p>
                        </div>
                    </div>
                    <div
                        class="max-w-64 min-w-48 rounded-lg bg-white/10 p-4 text-right text-sm"
                    >
                        <p class="text-xs text-white/70 uppercase">
                            Invoice No.
                        </p>
                        <p class="invoice-wrap font-semibold">
                            {{ props.invoiceNumber }}
                        </p>
                        <p class="mt-3 text-xs text-white/70 uppercase">
                            Invoice Date
                        </p>
                        <p>{{ props.invoiceDate || '-' }}</p>
                        <p class="mt-3 text-xs text-white/70 uppercase">
                            Due Date
                        </p>
                        <p>{{ props.dueDate || '-' }}</p>
                        <p class="mt-3 text-xs text-white/70 uppercase">
                            Status
                        </p>
                        <p class="capitalize">
                            {{ props.status || 'pending' }}
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
                        <p class="text-xs font-medium uppercase opacity-60">
                            Bill To
                        </p>
                        <p class="invoice-wrap mt-2 text-lg font-semibold">
                            {{ props.billTo || '-' }}
                        </p>
                        <p class="invoice-wrap mt-1 opacity-75">
                            {{ props.projectName || 'Project billing' }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border p-4"
                        :style="{ borderColor: 'var(--invoice-border)' }"
                    >
                        <p class="text-xs font-medium uppercase opacity-60">
                            Billing Summary
                        </p>
                        <p class="invoice-wrap mt-2 opacity-75">
                            {{ props.description || 'Project billing' }}
                        </p>
                        <p class="mt-2 capitalize opacity-75">
                            Status: {{ props.status || 'pending' }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="props.variant === 'summary'"
                    class="grid gap-6 sm:grid-cols-2"
                >
                    <div
                        class="rounded-lg border p-4"
                        :style="{ borderColor: 'var(--invoice-border)' }"
                    >
                        <p class="text-xs font-medium uppercase opacity-60">
                            From
                        </p>
                        <p class="mt-2 font-semibold">PT. Jasa Tirta Energi</p>
                        <p class="invoice-wrap mt-1 opacity-75">
                            Jl. Surabaya No. 2A, Malang, Indonesia
                        </p>
                        <p class="opacity-75">NPWP: 00.000.000.0-000.000</p>
                    </div>
                    <div
                        class="rounded-lg border p-4"
                        :style="{ borderColor: 'var(--invoice-border)' }"
                    >
                        <p class="text-xs font-medium uppercase opacity-60">
                            Payment Terms
                        </p>
                        <p class="mt-2 font-semibold">Due on receipt</p>
                        <p class="mt-1 opacity-75">
                            Please include the invoice number as the payment
                            reference.
                        </p>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-lg border p-4"
                    :style="{ borderColor: 'var(--invoice-border)' }"
                >
                    <p class="text-xs font-medium uppercase opacity-60">
                        Payment Details
                    </p>
                    <p class="mt-2 whitespace-pre-line opacity-75">
                        {{ template.bankDetails }}
                    </p>
                </div>

                <div
                    class="overflow-hidden rounded-lg border"
                    :style="{ borderColor: 'var(--invoice-border)' }"
                >
                    <table class="w-full table-fixed border-collapse">
                        <colgroup>
                            <col
                                :style="{
                                    width:
                                        props.variant === 'summary'
                                            ? '34%'
                                            : '38%',
                                }"
                            />
                            <col
                                v-if="props.variant === 'summary'"
                                style="width: 26%"
                            />
                            <col style="width: 10%" />
                            <col
                                v-if="props.variant === 'detail'"
                                style="width: 12%"
                            />
                            <col style="width: 15%" />
                            <col style="width: 15%" />
                        </colgroup>
                        <thead
                            :style="{
                                background: 'var(--invoice-accent)',
                                color: 'white',
                            }"
                        >
                            <tr>
                                <th class="px-4 py-3 text-left align-top">
                                    Description
                                </th>
                                <th
                                    v-if="props.variant === 'summary'"
                                    class="px-4 py-3 text-left align-top"
                                >
                                    Project
                                </th>
                                <th class="px-4 py-3 text-right align-top">
                                    Qty
                                </th>
                                <th
                                    v-if="props.variant === 'detail'"
                                    class="px-4 py-3 text-left align-top"
                                >
                                    Unit
                                </th>
                                <th class="px-4 py-3 text-right align-top">
                                    Unit Price
                                </th>
                                <th class="px-4 py-3 text-right align-top">
                                    Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in props.lineItems"
                                :key="item.id ?? index"
                                class="border-b"
                                :style="{
                                    borderColor: 'var(--invoice-border)',
                                }"
                            >
                                <td class="px-4 py-3 align-top">
                                    <p class="invoice-wrap font-medium">
                                        {{ item.description || '-' }}
                                    </p>
                                    <p
                                        v-if="item.category"
                                        class="invoice-wrap text-xs opacity-60"
                                    >
                                        {{ item.category }}
                                    </p>
                                </td>
                                <td
                                    v-if="props.variant === 'summary'"
                                    class="invoice-wrap px-4 py-3 align-top"
                                >
                                    {{
                                        item.projectName ||
                                        props.projectName ||
                                        '-'
                                    }}
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    {{ item.quantity ?? 1 }}
                                </td>
                                <td
                                    v-if="props.variant === 'detail'"
                                    class="invoice-wrap px-4 py-3 align-top"
                                >
                                    {{ item.unit || '-' }}
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    {{ formatCurrency(item.unitPrice) }}
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    {{ formatCurrency(item.totalPrice) }}
                                </td>
                            </tr>
                            <tr v-if="props.lineItems.length === 0">
                                <td
                                    :colspan="
                                        props.variant === 'summary' ? 5 : 5
                                    "
                                    class="px-4 py-8 text-center opacity-60"
                                >
                                    No invoice line items yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="grid gap-6 sm:grid-cols-[1fr_18rem]">
                    <div class="space-y-4">
                        <div
                            v-if="
                                template.showBankDetails &&
                                props.variant === 'summary'
                            "
                        >
                            <p class="font-medium">Bank Details</p>
                            <p
                                class="invoice-wrap mt-1 whitespace-pre-line opacity-75"
                            >
                                {{ template.bankDetails }}
                            </p>
                        </div>
                        <div v-if="template.showNotes">
                            <p class="font-medium">Notes</p>
                            <p
                                class="invoice-wrap mt-1 whitespace-pre-line opacity-75"
                            >
                                {{ template.notes }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between gap-4">
                            <span>Subtotal</span>
                            <span class="shrink-0">{{
                                formatCurrency(props.subtotal)
                            }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span>Tax</span>
                            <span class="shrink-0">{{
                                formatCurrency(props.tax)
                            }}</span>
                        </div>
                        <div
                            class="mt-3 flex justify-between gap-4 border-t pt-3 text-base font-semibold"
                            :style="{ borderColor: 'var(--invoice-border)' }"
                        >
                            <span>Total</span>
                            <span class="shrink-0">{{
                                formatCurrency(props.total)
                            }}</span>
                        </div>
                    </div>
                </div>

                <div
                    v-if="template.showSignature"
                    class="mt-auto grid gap-8 pt-8 sm:grid-cols-2"
                >
                    <div
                        class="rounded-lg border p-4"
                        :style="{ borderColor: 'var(--invoice-border)' }"
                    >
                        <p class="font-medium">
                            {{
                                props.variant === 'summary'
                                    ? 'Approval'
                                    : 'Notes'
                            }}
                        </p>
                        <p class="invoice-wrap mt-2 leading-relaxed opacity-75">
                            {{
                                props.variant === 'summary'
                                    ? 'This invoice is issued for the project scope stated above and is valid without a physical stamp when printed from the system.'
                                    : template.notes
                            }}
                        </p>
                    </div>
                    <div class="ml-auto w-48 text-center">
                        <div
                            class="mb-16 border-t"
                            :style="{ borderColor: 'var(--invoice-border)' }"
                        ></div>
                        <p class="font-medium">Authorized Signature</p>
                    </div>
                </div>
            </div>

            <div
                v-if="template.footerText"
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
</template>

<style>
.invoice-sheet {
    color: var(--invoice-text);
    background: var(--invoice-paper);
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
}

.invoice-sheet,
.invoice-sheet * {
    box-sizing: border-box;
}

.invoice-wrap {
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
}

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
        margin: 10mm;
    }

    html,
    body {
        width: 210mm;
        min-height: 297mm;
        margin: 0 !important;
        background: white !important;
    }

    body * {
        visibility: hidden;
    }

    .invoice-print-area,
    .invoice-print-area * {
        visibility: visible;
    }

    .invoice-print-area {
        position: fixed !important;
        inset: 0 !important;
        width: 100% !important;
        min-height: 100% !important;
        padding: 0 !important;
        overflow: visible !important;
        border-radius: 0 !important;
        background: white !important;
    }

    .invoice-sheet {
        width: 190mm !important;
        min-height: 277mm !important;
        margin: 0 auto !important;
        box-shadow: none !important;
        page-break-after: avoid;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .no-print {
        display: none !important;
    }
}
</style>
