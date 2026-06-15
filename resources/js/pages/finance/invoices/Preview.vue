<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Printer } from 'lucide-vue-next';
import InvoicePrintPreview from '@/components/invoice/InvoicePrintPreview.vue';
import { Button } from '@/components/ui/button';
import type { InvoicePrintLineItem } from '@/components/invoice/InvoicePrintPreview.vue';

type InvoiceValue = null | number | string;

const props = defineProps<{
    invoice: Record<string, InvoiceValue>;
    lineItems: InvoicePrintLineItem[];
    subtotal: number;
    tax: number;
    total: number;
}>();

const invoiceNumber = String(
    props.invoice.invoice_number || `Invoice #${props.invoice.id}`,
);

const printPreview = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Preview ${invoiceNumber}`" />

    <main class="invoice-preview-page min-h-screen bg-muted/40 p-3 sm:p-6">
        <div
            class="no-print mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 pb-4"
        >
            <Button variant="outline" @click="router.get('/invoices')">
                <ArrowLeft class="size-4" />
                Kembali ke Invoice
            </Button>
            <Button @click="printPreview">
                <Printer class="size-4" />
                Cetak / Simpan PDF
            </Button>
        </div>

        <InvoicePrintPreview
            :bill-to="props.invoice.project_name"
            :description="props.invoice.description"
            :due-date="props.invoice.due_date"
            :invoice-date="props.invoice.invoice_date"
            :invoice-number="invoiceNumber"
            :line-items="props.lineItems"
            :project-name="props.invoice.project_name"
            :status="props.invoice.status"
            :subtotal="props.subtotal"
            :tax="props.tax"
            :total="props.total"
            variant="summary"
        />
    </main>
</template>
