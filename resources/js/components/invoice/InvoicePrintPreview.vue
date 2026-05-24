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
    bankDetails: 'Bank Mandiri\nAccount Name: PT. Jasa Tirta Energi\nAccount No: 000-000-0000',
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
    <section class="invoice-print-area overflow-x-auto rounded-lg bg-muted/30 p-3">
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
                            <p class="mt-3 max-w-sm text-xs leading-relaxed text-white/80">
                                Jl. Surabaya No. 2A, Malang, Indonesia<br />
                                finance@jasatirtaenergi.co.id | +62 341 000 000
                            </p>
                        </div>
                    </div>
                    <div class="min-w-48 rounded-lg bg-white/10 p-4 text-right text-sm">
                        <p class="text-xs uppercase text-white/70">
                            Invoice No.
                        </p>
                        <p class="font-semibold">{{ props.invoiceNumber }}</p>
                        <p class="mt-3 text-xs uppercase text-white/70">
                            Invoice Date
                        </p>
                        <p>{{ props.invoiceDate || '-' }}</p>
                        <p class="mt-3 text-xs uppercase text-white/70">
                            Due Date
                        </p>
                        <p>{{ props.dueDate || '-' }}</p>
                        <p class="mt-3 text-xs uppercase text-white/70">
                            Status
                        </p>
                        <p class="capitalize">{{ props.status || 'pending' }}</p>
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
                        <p class="mt-2 text-lg font-semibold">
                            {{ props.billTo || '-' }}
                        </p>
                        <p class="mt-1 opacity-75">
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
                        <p class="mt-2 opacity-75">
                            {{ props.description || 'Project billing' }}
                        </p>
                        <p class="mt-2 capitalize opacity-75">
                            Status: {{ props.status || 'pending' }}
                        </p>
                    </div>
                </div>

                <div v-if="props.variant === 'summary'" class="grid gap-6 sm:grid-cols-2">
                    <div
                        class="rounded-lg border p-4"
                        :style="{ borderColor: 'var(--invoice-border)' }"
                    >
                        <p class="text-xs font-medium uppercase opacity-60">
                            From
                        </p>
                        <p class="mt-2 font-semibold">PT. Jasa Tirta Energi</p>
                        <p class="mt-1 opacity-75">
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
                                <th
                                    v-if="props.variant === 'summary'"
                                    class="px-4 py-3 text-left"
                                >
                                    Project
                                </th>
                                <th class="px-4 py-3 text-right">Qty</th>
                                <th
                                    v-if="props.variant === 'detail'"
                                    class="px-4 py-3 text-left"
                                >
                                    Unit
                                </th>
                                <th class="px-4 py-3 text-right">
                                    Unit Price
                                </th>
                                <th class="px-4 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in props.lineItems"
                                :key="item.id ?? index"
                                class="border-b"
                                :style="{ borderColor: 'var(--invoice-border)' }"
                            >
                                <td class="px-4 py-3">
                                    <p class="font-medium">
                                        {{ item.description || '-' }}
                                    </p>
                                    <p
                                        v-if="item.category"
                                        class="text-xs opacity-60"
                                    >
                                        {{ item.category }}
                                    </p>
                                </td>
                                <td
                                    v-if="props.variant === 'summary'"
                                    class="px-4 py-3"
                                >
                                    {{ item.projectName || props.projectName || '-' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ item.quantity ?? 1 }}
                                </td>
                                <td
                                    v-if="props.variant === 'detail'"
                                    class="px-4 py-3"
                                >
                                    {{ item.unit || '-' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ formatCurrency(item.unitPrice) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ formatCurrency(item.totalPrice) }}
                                </td>
                            </tr>
                            <tr v-if="props.lineItems.length === 0">
                                <td
                                    :colspan="props.variant === 'summary' ? 5 : 5"
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
                        <div v-if="template.showBankDetails && props.variant === 'summary'">
                            <p class="font-medium">Bank Details</p>
                            <p class="mt-1 whitespace-pre-line opacity-75">
                                {{ template.bankDetails }}
                            </p>
                        </div>
                        <div v-if="template.showNotes">
                            <p class="font-medium">Notes</p>
                            <p class="mt-1 whitespace-pre-line opacity-75">
                                {{ template.notes }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between gap-4">
                            <span>Subtotal</span>
                            <span>{{ formatCurrency(props.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span>Tax</span>
                            <span>{{ formatCurrency(props.tax) }}</span>
                        </div>
                        <div
                            class="mt-3 flex justify-between gap-4 border-t pt-3 text-base font-semibold"
                            :style="{ borderColor: 'var(--invoice-border)' }"
                        >
                            <span>Total</span>
                            <span>{{ formatCurrency(props.total) }}</span>
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
                            {{ props.variant === 'summary' ? 'Approval' : 'Notes' }}
                        </p>
                        <p class="mt-2 leading-relaxed opacity-75">
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
