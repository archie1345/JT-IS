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
        'Bank Mandiri\nNama Rekening: PT. Jasa Tirta Energi\nNo. Rekening: 000-000-0000',
    notes: 'Mohon cantumkan nomor invoice pada referensi pembayaran. Terima kasih.',
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

const invoiceStatusLabels: Record<string, string> = {
    pending: 'Menunggu',
    paid: 'Lunas',
    overdue: 'Terlambat',
};

const lineItemColspan = computed(() => (props.variant === 'summary' ? 4 : 5));

const statusLabel = computed(
    () =>
        invoiceStatusLabels[String(props.status ?? '').toLowerCase()] ??
        props.status ??
        'Menunggu',
);
</script>

<template>
    <section
        class="invoice-print-area max-w-full rounded-lg bg-muted/30 p-3 sm:p-4"
    >
        <div
            class="invoice-sheet mx-auto flex flex-col bg-white text-[#111827] shadow-lg"
            :style="invoiceStyle"
        >
            <div
                class="invoice-header px-8 py-7"
                :style="{
                    background: 'var(--invoice-accent)',
                    color: 'white',
                }"
            >
                <div class="grid gap-6 sm:grid-cols-[1fr_auto]">
                    <div class="flex min-w-0 items-start gap-4">
                        <div
                            class="flex size-16 shrink-0 items-center justify-center rounded-lg bg-white p-2"
                        >
                            <img
                                src="/assets/svg/JTE_Logo_only.svg"
                                alt="JTE"
                                class="max-h-full max-w-full object-contain"
                            />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm tracking-[0.2em] uppercase">
                                PT. Jasa Tirta Energi
                            </p>
                            <h1 class="mt-3 text-3xl font-semibold sm:text-4xl">
                                {{ template.title }}
                            </h1>
                            <p
                                class="invoice-wrap mt-3 max-w-sm text-xs leading-relaxed text-white/80"
                            >
                                Jl. Surabaya No. 2A, Malang, Indonesia<br />
                                finance@jasatirtaenergi.co.id | +62 341 000 000
                            </p>
                        </div>
                    </div>
                    <div
                        class="min-w-0 rounded-lg bg-white/10 p-4 text-left text-sm sm:min-w-48 sm:text-right"
                    >
                        <p class="text-xs text-white/70 uppercase">
                            No. Invoice
                        </p>
                        <p class="invoice-wrap font-semibold">
                            {{ props.invoiceNumber }}
                        </p>
                        <p class="mt-3 text-xs text-white/70 uppercase">
                            Tanggal Invoice
                        </p>
                        <p>{{ props.invoiceDate || '-' }}</p>
                        <p class="mt-3 text-xs text-white/70 uppercase">
                            Jatuh Tempo
                        </p>
                        <p>{{ props.dueDate || '-' }}</p>
                        <p class="mt-3 text-xs text-white/70 uppercase">
                            Status
                        </p>
                        <p>
                            {{ statusLabel }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="invoice-body flex flex-1 flex-col gap-7 px-8 py-7 text-sm"
                :style="{
                    color: 'var(--invoice-text)',
                    background: 'var(--invoice-paper)',
                }"
            >
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium uppercase opacity-60">
                            Ditagihkan Kepada
                        </p>
                        <p class="invoice-wrap mt-2 text-lg font-semibold">
                            {{ props.billTo || '-' }}
                        </p>
                        <p class="invoice-wrap mt-1 opacity-75">
                            {{ props.projectName || 'Invoice proyek' }}
                        </p>
                    </div>
                    <div
                        class="rounded-lg border p-4"
                        :style="{ borderColor: 'var(--invoice-border)' }"
                    >
                        <p class="text-xs font-medium uppercase opacity-60">
                            Ringkasan Billing
                        </p>
                        <p class="invoice-wrap mt-2 opacity-75">
                            {{ props.description || 'Invoice proyek' }}
                        </p>
                        <p class="mt-2 capitalize opacity-75">
                            Status: {{ statusLabel }}
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
                            Dari
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
                            Syarat Pembayaran
                        </p>
                        <p class="mt-2 font-semibold">Dibayar saat diterima</p>
                        <p class="mt-1 opacity-75">
                            Mohon cantumkan nomor invoice sebagai referensi
                            pembayaran.
                        </p>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-lg border p-4"
                    :style="{ borderColor: 'var(--invoice-border)' }"
                >
                    <p class="text-xs font-medium uppercase opacity-60">
                        Detail Pembayaran
                    </p>
                    <p class="mt-2 whitespace-pre-line opacity-75">
                        {{ template.bankDetails }}
                    </p>
                </div>

                <div
                    class="invoice-table-frame rounded-lg border"
                    :style="{ borderColor: 'var(--invoice-border)' }"
                >
                    <table class="invoice-line-table">
                        <colgroup>
                            <col
                                :style="{
                                    width:
                                        props.variant === 'summary'
                                            ? '50%'
                                            : '40%',
                                }"
                            />
                            <col
                                :style="{
                                    width:
                                        props.variant === 'summary'
                                            ? '10%'
                                            : '10%',
                                }"
                            />
                            <col
                                v-if="props.variant === 'detail'"
                                style="width: 12%"
                            />
                            <col
                                :style="{
                                    width:
                                        props.variant === 'summary'
                                            ? '20%'
                                            : '19%',
                                }"
                            />
                            <col
                                :style="{
                                    width:
                                        props.variant === 'summary'
                                            ? '20%'
                                            : '19%',
                                }"
                            />
                        </colgroup>

                        <thead>
                            <tr>
                                <th class="invoice-table-desc">Deskripsi</th>
                                <th class="invoice-table-qty">Qty</th>
                                <th
                                    v-if="props.variant === 'detail'"
                                    class="invoice-table-unit"
                                >
                                    Unit
                                </th>
                                <th class="invoice-table-money">
                                    Harga Satuan
                                </th>
                                <th class="invoice-table-money">Jumlah</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="(item, index) in props.lineItems"
                                :key="item.id ?? index"
                            >
                                <td class="invoice-table-desc">
                                    <p class="invoice-wrap font-medium">
                                        {{ item.description || '-' }}
                                    </p>

                                    <p
                                        v-if="item.category"
                                        class="invoice-wrap text-xs opacity-60"
                                    >
                                        {{ item.category }}
                                    </p>

                                    <p
                                        v-if="
                                            props.variant === 'summary' &&
                                            (item.projectName ||
                                                props.projectName)
                                        "
                                        class="invoice-wrap text-xs opacity-60"
                                    >
                                        {{
                                            item.projectName ||
                                            props.projectName
                                        }}
                                    </p>
                                </td>

                                <td class="invoice-table-qty">
                                    {{ item.quantity ?? 1 }}
                                </td>

                                <td
                                    v-if="props.variant === 'detail'"
                                    class="invoice-table-unit"
                                >
                                    {{ item.unit || '-' }}
                                </td>

                                <td class="invoice-table-money">
                                    <span class="invoice-money">
                                        {{ formatCurrency(item.unitPrice) }}
                                    </span>
                                </td>

                                <td class="invoice-table-money">
                                    <span class="invoice-money">
                                        {{ formatCurrency(item.totalPrice) }}
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="props.lineItems.length === 0">
                                <td
                                    :colspan="lineItemColspan"
                                    class="invoice-table-empty"
                                >
                                    Belum ada item invoice.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="invoice-ending space-y-7">
                    <div class="grid gap-6 sm:grid-cols-[minmax(0,1fr)_16rem]">
                        <div class="space-y-4">
                            <div
                                v-if="
                                    template.showBankDetails &&
                                    props.variant === 'summary'
                                "
                            >
                                <p class="font-medium">Detail Bank</p>
                                <p
                                    class="invoice-wrap mt-1 whitespace-pre-line opacity-75"
                                >
                                    {{ template.bankDetails }}
                                </p>
                            </div>
                            <div v-if="template.showNotes">
                                <p class="font-medium">Catatan</p>
                                <p
                                    class="invoice-wrap mt-1 whitespace-pre-line opacity-75"
                                >
                                    {{ template.notes }}
                                </p>
                            </div>
                        </div>

                        <div class="invoice-total-box space-y-2">
                            <div class="flex justify-between gap-4">
                                <span>Subtotal</span>
                                <span class="shrink-0">{{
                                    formatCurrency(props.subtotal)
                                }}</span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span>Pajak</span>
                                <span class="shrink-0">{{
                                    formatCurrency(props.tax)
                                }}</span>
                            </div>
                            <div
                                class="mt-3 flex justify-between gap-4 border-t pt-3 text-base font-semibold"
                                :style="{
                                    borderColor: 'var(--invoice-border)',
                                }"
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
                        class="invoice-signature mt-auto grid gap-8 pt-8 sm:grid-cols-2"
                    >
                        <div
                            class="rounded-lg border p-4"
                            :style="{ borderColor: 'var(--invoice-border)' }"
                        >
                            <p class="font-medium">
                                {{
                                    props.variant === 'summary'
                                        ? 'Approval'
                                        : 'Catatan'
                                }}
                            </p>
                            <p
                                class="invoice-wrap mt-2 leading-relaxed opacity-75"
                            >
                                {{
                                    props.variant === 'summary'
                                        ? 'Invoice ini diterbitkan untuk lingkup proyek di atas dan sah saat dicetak dari sistem.'
                                        : template.notes
                                }}
                            </p>
                        </div>
                        <div class="ml-auto w-48 text-center">
                            <div
                                class="mb-16 border-t"
                                :style="{
                                    borderColor: 'var(--invoice-border)',
                                }"
                            ></div>
                            <p class="font-medium">Tanda Tangan Berwenang</p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="template.footerText"
                class="invoice-footer border-t px-10 py-4 text-center text-xs opacity-70"
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
.invoice-print-area {
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}

.invoice-sheet {
    color: var(--invoice-text);
    background: var(--invoice-paper);
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
    width: min(210mm, calc(100vw - 2rem));
    max-width: 210mm;
    min-height: min(297mm, calc(141.4286vw - 2.8286rem));
    overflow-x: hidden;
    overflow-y: visible;
}

.invoice-sheet,
.invoice-sheet * {
    box-sizing: border-box;
}

.invoice-wrap {
    min-width: 0;
    max-width: 100%;
    overflow-wrap: break-word;
    word-break: normal;
    white-space: normal;
}

.invoice-table-frame {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow: hidden;
    border-radius: 0.5rem;
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.invoice-table-frame::-webkit-scrollbar {
    display: none;
    width: 0;
    height: 0;
}

.invoice-line-table {
    table-layout: fixed;
    width: 100%;
    max-width: 100%;
    min-width: 0;
    border-collapse: collapse;
}

.invoice-line-table th,
.invoice-line-table td {
    min-width: 0;
    max-width: 100%;
    padding: 0.75rem 1rem;
    overflow-wrap: break-word;
    word-break: normal;
    white-space: normal;
    line-height: 1.35;
    vertical-align: top;
}

.invoice-line-table th {
    background: var(--invoice-accent);
    color: white;
    font-weight: 600;
    line-height: 1.25;
}

.invoice-line-table tbody tr {
    border-bottom: 1px solid var(--invoice-border);
}

.invoice-line-table tbody tr:last-child {
    border-bottom: 0;
}

.invoice-table-desc {
    text-align: left;
}

.invoice-table-qty,
.invoice-table-money {
    text-align: right;
}

.invoice-table-unit {
    text-align: left;
}

.invoice-money {
    white-space: nowrap;
    overflow-wrap: normal;
    word-break: normal;
}

.invoice-table-empty {
    padding: 2rem 1rem;
    text-align: center;
    opacity: 0.6;
}

.invoice-ending,
.invoice-signature,
.invoice-footer {
    break-inside: avoid;
    page-break-inside: avoid;
}

.invoice-total-box {
    min-width: 0;
}

.invoice-total-box span {
    min-width: 0;
    overflow-wrap: break-word;
    word-break: normal;
}

@media (max-width: 640px) {
    .invoice-body {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }

    .invoice-line-table {
        font-size: 0.75rem;
    }

    .invoice-line-table th,
    .invoice-line-table td {
        padding: 0.65rem 0.5rem;
    }

    .invoice-money {
        white-space: normal;
        overflow-wrap: break-word;
    }
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

    .invoice-preview-page {
        min-height: auto !important;
        padding: 0 !important;
        background: white !important;
    }

    .invoice-print-area,
    .invoice-print-area * {
        visibility: visible;
    }

    .invoice-print-area {
        position: static !important;
        width: auto !important;
        max-width: none !important;
        min-height: auto !important;
        padding: 0 !important;
        overflow: visible !important;
        border-radius: 0 !important;
        background: white !important;
    }

    .invoice-sheet {
        width: 190mm !important;
        max-width: 190mm !important;
        min-height: auto !important;
        overflow: visible !important;
        margin: 0 auto !important;
        box-shadow: none !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .invoice-header,
    .invoice-body > .grid,
    .invoice-body > .rounded-lg,
    .invoice-ending,
    .invoice-signature,
    .invoice-footer {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .invoice-table-frame {
        overflow: visible !important;
        break-inside: auto;
        page-break-inside: auto;
    }

    .invoice-line-table {
        page-break-inside: auto;
    }

    .invoice-line-table thead {
        display: table-header-group;
    }

    .invoice-line-table tbody {
        display: table-row-group;
    }

    .invoice-line-table tr {
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .invoice-ending {
        margin-top: 1.75rem;
    }

    .no-print {
        display: none !important;
    }
}
</style>
