<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ArrowLeft,
    FileText,
    Pencil,
    Plus,
    Save,
    Trash2,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import EntityDetailHero from '@/components/entity/EntityDetailHero.vue';
import EntityMetricCard from '@/components/entity/EntityMetricCard.vue';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import InputError from '@/components/InputError.vue';
import DocumentUploadPanel from '@/components/shared/DocumentUploadPanel.vue';
import ProjectOCRScanner from '@/components/ProjectOCRScanner.vue';
import RecordFieldInput from '@/components/prototype/RecordFieldInput.vue';
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
import { extractImportantDocumentData } from '@/lib/documentExtraction';
import { formatCurrency } from '@/lib/formatters';
import type { BreadcrumbItem } from '@/types';
import type { UploadedDocument } from '@/types/project';

type RecordValue = null | number | string;
type Option = { value: number | string; label: string; hint?: null | string };
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
type FinancialItem = {
    id: number;
    sourceType: null | string;
    sourceItemId: null | number;
    category: null | string;
    description: null | string;
    unit: null | string;
    quantity: number;
    unitPrice: number;
    totalPrice: number;
    vendor: null | string;
    notes: null | string;
};
type BudgetItemOption = {
    value: string;
    sourceType: string;
    sourceItemId: number;
    label: string;
    hint: null | string;
    category: null | string;
    description: null | string;
    unit: null | string;
    quantity: number;
    unitPrice: number;
    totalPrice: number;
};

const props = defineProps<{
    kind: 'cost' | 'invoice';
    title: string;
    recordLabel: string;
    indexUrl: string;
    updateUrl: string;
    itemStoreUrl: string;
    itemUpdateUrlBase: string;
    breadcrumbs: BreadcrumbItem[];
    fields: Field[];
    record: Record<string, RecordValue>;
    items: FinancialItem[];
    budgetItemOptions: BudgetItemOption[];
    summary: {
        subtotal: number;
        tax: number;
        total: number;
        itemCount: number;
    };
    upload: {
        componentType: string;
        componentId: number;
        projectId?: null | number;
        documents: UploadedDocument[];
    };
}>();

const isItemOpen = ref(false);
const editingItemId = ref<null | number>(null);
const selectedSource = ref('');
const deletingItemId = ref<null | number>(null);
const scannerText = ref('');
const scannerApplied = ref(false);

const headerForm = useForm<Record<string, number | string>>(
    Object.fromEntries(
        props.fields.map((field) => [
            field.name,
            props.record[field.name] ?? '',
        ]),
    ),
);

const itemForm = useForm({
    source_type: 'manual',
    source_item_id: '',
    category: '',
    description: '',
    unit: '',
    quantity: 1,
    unit_price: 0,
    total_price: 0,
    vendor: '',
    notes: '',
});

const documentTitle = computed(() =>
    String(
        props.record.invoice_number ||
            props.record.reference_number ||
            `${props.recordLabel} #${props.record.id}`,
    ),
);

const itemDialogTitle = computed(() =>
    editingItemId.value === null ? 'Tambah item' : 'Edit item',
);

const itemTotal = computed(
    () => Number(itemForm.quantity || 0) * Number(itemForm.unit_price || 0),
);
const scannerData = computed(() =>
    scannerText.value.trim()
        ? extractImportantDocumentData(
              scannerText.value,
              props.upload.componentType,
          )
        : null,
);
const scannerRows = computed(() =>
    (scannerData.value?.grouping_results ?? []).flatMap((category) =>
        category.sub_categories.flatMap((subCategory) =>
            subCategory.items.map((item) => ({
                category: category.category,
                description: item.description,
                unit: item.unit ?? '',
                quantity: item.volume ?? 1,
                unitPrice: item.unit_price ?? 0,
                totalPrice:
                    item.total ??
                    Number(item.volume ?? 0) * Number(item.unit_price ?? 0),
            })),
        ),
    ),
);
const scannerSummary = computed(() => {
    if (!scannerData.value) {
        return 'Upload file untuk scan detail invoice atau biaya.';
    }

    const metadata = scannerData.value.metadata;
    const detected = [
        metadata.doc_number ? 'nomor dokumen' : null,
        metadata.contract_value ? 'nilai' : null,
        metadata.contract_date ? 'tanggal' : null,
        scannerRows.value.length > 0
            ? `${scannerRows.value.length} baris item`
            : null,
    ].filter(Boolean);

    return detected.length > 0
        ? `Terdeteksi ${detected.join(', ')}.`
        : 'OCR selesai, tetapi tidak ada field terstruktur yang terdeteksi.';
});

const backToList = () => router.get(props.indexUrl);
const refreshPage = () => router.reload();
const toDateInputValue = (value: null | string | undefined) =>
    value && /^\d{4}-\d{2}-\d{2}$/.test(value) ? value : '';

const submitHeader = () => {
    headerForm.patch(props.updateUrl, {
        preserveScroll: true,
        onSuccess: refreshPage,
    });
};

const resetItemForm = () => {
    editingItemId.value = null;
    selectedSource.value = '';
    itemForm.defaults({
        source_type: 'manual',
        source_item_id: '',
        category: '',
        description: '',
        unit: '',
        quantity: 1,
        unit_price: 0,
        total_price: 0,
        vendor: '',
        notes: '',
    });
    itemForm.reset();
    itemForm.clearErrors();
};

const openCreateItem = () => {
    resetItemForm();
    isItemOpen.value = true;
};

const openEditItem = (item: FinancialItem) => {
    editingItemId.value = item.id;
    selectedSource.value =
        item.sourceType && item.sourceItemId
            ? `${item.sourceType}:${item.sourceItemId}`
            : '';

    const payload = {
        source_type: item.sourceType ?? 'manual',
        source_item_id: item.sourceItemId ? String(item.sourceItemId) : '',
        category: item.category ?? '',
        description: item.description ?? '',
        unit: item.unit ?? '',
        quantity: item.quantity ?? 1,
        unit_price: item.unitPrice ?? 0,
        total_price: item.totalPrice ?? 0,
        vendor: item.vendor ?? '',
        notes: item.notes ?? '',
    };

    itemForm.defaults(payload);
    itemForm.reset();
    Object.assign(itemForm, payload);
    itemForm.clearErrors();
    isItemOpen.value = true;
};

const closeItemModal = () => {
    isItemOpen.value = false;
    resetItemForm();
};

const applyBudgetItem = () => {
    const option = props.budgetItemOptions.find(
        (item) => item.value === selectedSource.value,
    );

    if (!option) {
        itemForm.source_type = 'manual';
        itemForm.source_item_id = '';
        return;
    }

    itemForm.source_type = option.sourceType;
    itemForm.source_item_id = String(option.sourceItemId);
    itemForm.category = option.category ?? '';
    itemForm.description = option.description ?? '';
    itemForm.unit = option.unit ?? '';
    itemForm.quantity = option.quantity || 1;
    itemForm.unit_price = option.unitPrice || 0;
    itemForm.total_price = option.totalPrice || 0;
};

const handleScannerData = (text: string) => {
    scannerText.value = text;
    scannerApplied.value = false;
};

const applyScannerToHeader = () => {
    const metadata = scannerData.value?.metadata;

    if (!metadata) {
        return;
    }

    if (props.kind === 'invoice') {
        headerForm.invoice_number =
            metadata.doc_number || headerForm.invoice_number || '';
        headerForm.amount = metadata.contract_value ?? headerForm.amount ?? '';
        headerForm.invoice_date =
            toDateInputValue(metadata.contract_date) ||
            headerForm.invoice_date ||
            '';
        headerForm.description =
            metadata.project_name || headerForm.description || '';
    } else {
        headerForm.reference_number =
            metadata.doc_number || headerForm.reference_number || '';
        headerForm.amount = metadata.contract_value ?? headerForm.amount ?? '';
        headerForm.date =
            toDateInputValue(metadata.contract_date) || headerForm.date || '';
        headerForm.description =
            metadata.project_name || headerForm.description || '';
        headerForm.vendor = metadata.owner || headerForm.vendor || '';
    }

    scannerApplied.value = true;
};

const openScannerItem = () => {
    const row = scannerRows.value[0];

    resetItemForm();

    if (row) {
        itemForm.category = row.category;
        itemForm.description = row.description;
        itemForm.unit = row.unit;
        itemForm.quantity = Number(row.quantity || 1);
        itemForm.unit_price = Number(row.unitPrice || 0);
        itemForm.total_price = Number(row.totalPrice || 0);
    } else if (scannerData.value?.metadata.contract_value) {
        itemForm.description =
            scannerData.value.metadata.project_name || documentTitle.value;
        itemForm.quantity = 1;
        itemForm.unit = 'ls';
        itemForm.unit_price = Number(scannerData.value.metadata.contract_value);
        itemForm.total_price = Number(
            scannerData.value.metadata.contract_value,
        );
    }

    isItemOpen.value = true;
};

const submitItem = () => {
    const payload = {
        ...itemForm.data(),
        total_price: itemForm.total_price || itemTotal.value,
    };

    if (editingItemId.value === null) {
        router.post(props.itemStoreUrl, payload, {
            preserveScroll: true,
            onSuccess: () => {
                closeItemModal();
                refreshPage();
            },
        });
        return;
    }

    router.patch(`${props.itemUpdateUrlBase}/${editingItemId.value}`, payload, {
        preserveScroll: true,
        onSuccess: () => {
            closeItemModal();
            refreshPage();
        },
    });
};

const destroyItem = (item: FinancialItem) => {
    if (!window.confirm('Hapus item ini?')) return;

    deletingItemId.value = item.id;
    router.delete(`${props.itemUpdateUrlBase}/${item.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingItemId.value = null;
        },
        onSuccess: refreshPage,
    });
};

const openInvoicePreview = () => {
    window.open(`/invoices/${props.record.id}/preview`, '_blank', 'noopener');
};
</script>

<template>
    <Head :title="`${props.title} #${props.record.id}`" />

    <AppLayout :breadcrumbs="props.breadcrumbs">
        <div
            class="flex min-h-[calc(100vh-8rem)] min-w-0 flex-1 flex-col gap-3 rounded-xl p-2 sm:gap-4 sm:p-4"
        >
            <EntityDetailHero
                back-label="Kembali"
                :title="String(props.record.project_name || 'Proyek')"
                :description="documentTitle"
                :badge-text="props.recordLabel"
                badge-class="bg-emerald-500/15 text-emerald-600 ring-1 ring-emerald-500/25"
                @back="backToList"
            >
                <template #back>
                    <Button
                        variant="ghost"
                        class="mb-3 pl-0 text-muted-foreground"
                        @click="backToList"
                    >
                        <ArrowLeft class="mr-2 size-4" />
                        Kembali
                    </Button>
                </template>
            </EntityDetailHero>

            <section class="grid min-w-0 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <EntityMetricCard
                    label="Item"
                    :value="props.summary.itemCount"
                />
                <EntityMetricCard
                    label="Subtotal"
                    :value="formatCurrency(props.summary.subtotal)"
                />
                <EntityMetricCard
                    :label="
                        props.kind === 'invoice' ? 'Pajak' : 'Item Budget Terkait'
                    "
                    :value="
                        props.kind === 'invoice'
                            ? formatCurrency(props.summary.tax)
                            : props.budgetItemOptions.length
                    "
                />
                <EntityMetricCard
                    label="Total"
                    :value="formatCurrency(props.summary.total)"
                />
            </section>

            <section class="grid min-w-0 gap-4">
                <EntityPageSection
                    title="Field Data"
                    :description="`Edit field header untuk dokumen ${props.recordLabel.toLowerCase()} ini.`"
                >
                    <form
                        class="grid min-w-0 gap-4 sm:grid-cols-2"
                        @submit.prevent="submitHeader"
                    >
                        <RecordFieldInput
                            v-for="field in props.fields"
                            :key="field.name"
                            v-model="headerForm[field.name]"
                            :field="field"
                            :error="headerForm.errors[field.name]"
                        />

                        <div
                            class="flex flex-col justify-end gap-2 sm:col-span-2 sm:flex-row"
                        >
                            <Button
                                v-if="props.kind === 'invoice'"
                                type="button"
                                variant="outline"
                                @click="openInvoicePreview"
                            >
                                <FileText class="mr-2 size-4" />
                                Buat PDF
                            </Button>
                            <Button
                                type="submit"
                                :disabled="headerForm.processing"
                            >
                                <Save class="mr-2 size-4" />
                                Simpan Field Data
                            </Button>
                        </div>
                    </form>
                </EntityPageSection>

                <EntityPageSection
                    title="File Terunggah"
                    :description="`File yang terlampir ke data ${props.recordLabel.toLowerCase()} ini.`"
                >
                    <div
                        class="mb-4 grid min-w-0 gap-3 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)]"
                    >
                        <ProjectOCRScanner
                            @data-extracted="handleScannerData"
                        />
                        <div
                            class="min-w-0 rounded-xl border border-sidebar-border/70 bg-background p-3 shadow-xs sm:p-4 dark:border-sidebar-border"
                        >
                            <div
                                class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">
                                        Hasil Scanner OCR
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{ scannerSummary }}
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        :disabled="!scannerData"
                                        @click="applyScannerToHeader"
                                    >
                                        Isi Header
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        :disabled="!scannerData"
                                        @click="openScannerItem"
                                    >
                                        Tambah Baris OCR
                                    </Button>
                                </div>
                            </div>

                            <div
                                v-if="scannerData"
                                class="mt-4 grid min-w-0 gap-3 text-xs sm:grid-cols-2 sm:text-sm"
                            >
                                <div
                                    class="min-w-0 rounded-md bg-muted/40 px-3 py-2"
                                >
                                    <span
                                        class="block text-xs text-muted-foreground"
                                    >
                                        Referensi
                                    </span>
                                    <span class="block truncate font-medium">
                                        {{
                                            scannerData.metadata.doc_number ||
                                            '-'
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="min-w-0 rounded-md bg-muted/40 px-3 py-2"
                                >
                                    <span
                                        class="block text-xs text-muted-foreground"
                                    >
                                        Nilai
                                    </span>
                                    <span class="block truncate font-medium">
                                        {{
                                            formatCurrency(
                                                scannerData.metadata
                                                    .contract_value,
                                            )
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="min-w-0 rounded-md bg-muted/40 px-3 py-2"
                                >
                                    <span
                                        class="block text-xs text-muted-foreground"
                                    >
                                        Proyek / Deskripsi
                                    </span>
                                    <span class="block font-medium break-words">
                                        {{
                                            scannerData.metadata.project_name ||
                                            '-'
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="min-w-0 rounded-md bg-muted/40 px-3 py-2"
                                >
                                    <span
                                        class="block text-xs text-muted-foreground"
                                    >
                                        Baris Terdeteksi
                                    </span>
                                    <span class="font-medium">
                                        {{ scannerRows.length }}
                                    </span>
                                </div>
                            </div>

                            <p
                                v-if="scannerApplied"
                                class="mt-3 rounded-md border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700"
                            >
                                Nilai OCR disalin ke form. Review dulu, lalu
                                simpan field data.
                            </p>

                            <p
                                v-if="scannerText"
                                class="mt-3 max-h-28 overflow-y-auto rounded-md border border-sidebar-border/60 px-3 py-2 text-xs leading-relaxed whitespace-pre-wrap text-muted-foreground"
                            >
                                {{ scannerText.slice(0, 800) }}
                            </p>
                        </div>
                    </div>

                    <DocumentUploadPanel
                        :project-id="props.upload.projectId"
                        :component-type="props.upload.componentType"
                        :component-id="props.upload.componentId"
                        :documents="props.upload.documents"
                        :title="`File ${props.recordLabel}`"
                        :description="`Lampirkan invoice, bukti pembayaran, persetujuan, dan dokumen pendukung.`"
                    />
                </EntityPageSection>

                <EntityPageSection
                    title="Detail Item"
                    :description="`Pisahkan ${props.recordLabel.toLowerCase()} ini menjadi baris item tagihan atau realisasi.`"
                >
                    <div
                        class="flex min-w-0 flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 px-3 py-3 sm:px-5 sm:py-4"
                    >
                        <p
                            class="min-w-0 text-xs text-muted-foreground sm:text-sm"
                        >
                            Pilih dari item RAB/RAP proyek atau tambah baris
                            manual.
                        </p>
                        <Button @click="openCreateItem">
                            <Plus class="mr-2 size-4" />
                            Tambah baris
                        </Button>
                    </div>

                    <div class="relative min-w-0 overflow-x-hidden">
                        <div
                            class="table-scrollbar max-w-full overflow-x-auto pb-2"
                        >
                            <table class="w-max min-w-full text-xs sm:text-sm">
                                <thead
                                    class="sticky top-0 z-10 bg-muted/95 text-left text-muted-foreground backdrop-blur"
                                >
                                    <tr>
                                        <th
                                            class="min-w-[8rem] px-3 py-2.5 font-medium sm:min-w-[9rem] sm:px-4 sm:py-3"
                                        >
                                            Sumber
                                        </th>
                                        <th
                                            class="min-w-[16rem] px-3 py-2.5 font-medium sm:min-w-[20rem] sm:px-4 sm:py-3"
                                        >
                                            Deskripsi
                                        </th>
                                        <th
                                            v-if="props.kind === 'cost'"
                                            class="min-w-[10rem] px-3 py-2.5 font-medium sm:min-w-[12rem] sm:px-4 sm:py-3"
                                        >
                                            Vendor
                                        </th>
                                        <th
                                            class="min-w-[5rem] px-3 py-2.5 font-medium sm:min-w-[6rem] sm:px-4 sm:py-3"
                                        >
                                            Qty
                                        </th>
                                        <th
                                            class="min-w-[5rem] px-3 py-2.5 font-medium sm:min-w-[6rem] sm:px-4 sm:py-3"
                                        >
                                            Unit
                                        </th>
                                        <th
                                            class="min-w-[9rem] px-3 py-2.5 font-medium sm:min-w-[10rem] sm:px-4 sm:py-3"
                                        >
                                            Harga Satuan
                                        </th>
                                        <th
                                            class="min-w-[9rem] px-3 py-2.5 font-medium sm:min-w-[10rem] sm:px-4 sm:py-3"
                                        >
                                            Nilai
                                        </th>
                                        <th
                                            class="min-w-[6rem] px-3 py-2.5 text-right font-medium sm:min-w-[7rem] sm:px-4 sm:py-3"
                                        >
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="item in props.items"
                                        :key="item.id"
                                        class="border-t border-sidebar-border/70 align-top"
                                    >
                                        <td
                                            class="px-3 py-3 text-muted-foreground uppercase sm:px-4"
                                        >
                                            {{ item.sourceType || 'manual' }}
                                        </td>
                                        <td class="px-3 py-3 sm:px-4">
                                            <p
                                                v-if="item.category"
                                                class="mb-1 text-xs text-muted-foreground"
                                            >
                                                {{ item.category }}
                                            </p>
                                            <span class="break-words">
                                                {{ item.description || '-' }}
                                            </span>
                                        </td>
                                        <td
                                            v-if="props.kind === 'cost'"
                                            class="px-3 py-3 sm:px-4"
                                        >
                                            {{ item.vendor || '-' }}
                                        </td>
                                        <td class="px-3 py-3 sm:px-4">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="px-3 py-3 sm:px-4">
                                            {{ item.unit || '-' }}
                                        </td>
                                        <td class="px-3 py-3 sm:px-4">
                                            {{ formatCurrency(item.unitPrice) }}
                                        </td>
                                        <td
                                            class="px-3 py-3 font-medium sm:px-4"
                                        >
                                            {{
                                                formatCurrency(item.totalPrice)
                                            }}
                                        </td>
                                        <td class="px-3 py-3 sm:px-4">
                                            <div class="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    @click="openEditItem(item)"
                                                >
                                                    <Pencil class="size-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    class="text-destructive"
                                                    :disabled="
                                                        deletingItemId ===
                                                        item.id
                                                    "
                                                    @click="destroyItem(item)"
                                                >
                                                    <Trash2 class="size-4" />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="props.items.length === 0">
                                        <td
                                            :colspan="
                                                props.kind === 'cost' ? 8 : 7
                                            "
                                            class="px-4 py-8 text-center text-sm text-muted-foreground"
                                        >
                                            Belum ada item. Tambah baris dari
                                            RAB/RAP proyek untuk mulai
                                            rekonsiliasi.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </EntityPageSection>
            </section>
        </div>

        <Dialog v-model:open="isItemOpen">
            <DialogContent
                class="flex max-h-[calc(100dvh-2rem)] w-[calc(100vw-2rem)] !max-w-2xl flex-col overflow-hidden p-4 sm:p-6"
            >
                <DialogHeader class="shrink-0">
                    <DialogTitle>{{ itemDialogTitle }}</DialogTitle>
                    <DialogDescription>
                        Hubungkan item budget proyek atau isi baris manual.
                    </DialogDescription>
                </DialogHeader>

                <form
                    class="grid min-h-0 min-w-0 flex-1 gap-4 overflow-x-hidden overflow-y-auto py-2 pr-1 sm:grid-cols-2"
                    @submit.prevent="submitItem"
                >
                    <div class="min-w-0 space-y-2 sm:col-span-2">
                        <Label for="source_item">Pilih Item RAB/RAP</Label>
                        <select
                            id="source_item"
                            v-model="selectedSource"
                            class="h-10 w-full min-w-0 rounded-md border border-input bg-background px-3 text-sm"
                            @change="applyBudgetItem"
                        >
                            <option value="">Item manual</option>
                            <option
                                v-for="option in props.budgetItemOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.sourceType.toUpperCase() }} -
                                {{ option.label }}
                                {{ option.hint ? `(${option.hint})` : '' }}
                            </option>
                        </select>
                    </div>

                    <div class="min-w-0 space-y-2">
                        <Label for="category">Kategori</Label>
                        <Input id="category" v-model="itemForm.category" />
                        <InputError :message="itemForm.errors.category" />
                    </div>
                    <div v-if="props.kind === 'cost'" class="min-w-0 space-y-2">
                        <Label for="vendor">Vendor / Penerima</Label>
                        <Input id="vendor" v-model="itemForm.vendor" />
                        <InputError :message="itemForm.errors.vendor" />
                    </div>
                    <div class="min-w-0 space-y-2 sm:col-span-2">
                        <Label for="description">Deskripsi</Label>
                        <Input
                            id="description"
                            v-model="itemForm.description"
                        />
                        <InputError :message="itemForm.errors.description" />
                    </div>
                    <div class="min-w-0 space-y-2">
                        <Label for="quantity">Quantity</Label>
                        <Input
                            id="quantity"
                            v-model.number="itemForm.quantity"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <InputError :message="itemForm.errors.quantity" />
                    </div>
                    <div class="min-w-0 space-y-2">
                        <Label for="unit">Unit</Label>
                        <Input id="unit" v-model="itemForm.unit" />
                        <InputError :message="itemForm.errors.unit" />
                    </div>
                    <div class="min-w-0 space-y-2">
                        <Label for="unit_price">Harga Satuan</Label>
                        <Input
                            id="unit_price"
                            v-model.number="itemForm.unit_price"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <InputError :message="itemForm.errors.unit_price" />
                    </div>
                    <div class="min-w-0 space-y-2">
                        <Label for="total_price">Total Harga</Label>
                        <Input
                            id="total_price"
                            v-model.number="itemForm.total_price"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <p class="text-xs text-muted-foreground">
                            Terhitung: {{ formatCurrency(itemTotal) }}
                        </p>
                        <InputError :message="itemForm.errors.total_price" />
                    </div>
                    <div class="min-w-0 space-y-2 sm:col-span-2">
                        <Label for="notes">Catatan</Label>
                        <textarea
                            id="notes"
                            v-model="itemForm.notes"
                            class="flex min-h-24 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                        />
                        <InputError :message="itemForm.errors.notes" />
                    </div>

                    <DialogFooter class="shrink-0 sm:col-span-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeItemModal"
                            >Batal</Button
                        >
                        <Button type="submit" :disabled="itemForm.processing">
                            {{ editingItemId === null ? 'Simpan' : 'Update' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
