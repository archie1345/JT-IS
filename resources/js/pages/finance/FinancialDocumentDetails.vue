<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    ArrowLeft,
    FileText,
    Pencil,
    Plus,
    Save,
    Search,
    Trash2,
    LoaderCircle,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import EntityDetailHero from '@/components/entity/EntityDetailHero.vue';
import EntityMetricCard from '@/components/entity/EntityMetricCard.vue';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import InputError from '@/components/InputError.vue';
import DocumentUploadPanel from '@/components/shared/DocumentUploadPanel.vue';
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
import { csrfFetch } from '@/lib/ocr';
import { formatCurrency } from '@/lib/formatters';
import type { BreadcrumbItem } from '@/types';
import type { UploadedDocument } from '@/types/project';
import DocumentList from '@/components/shared/DocumentList.vue';

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
    documentNumber?: null | string;
    documentDate?: null | string;
    itemCount?: number;
    totalBudget?: number;
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
const selectedBudgetValues = ref<string[]>([]);
const deletingItemId = ref<null | number>(null);
const activeBudgetType = ref<'rab' | 'rap'>('rab');
const isBulkSubmitting = ref(false);
const budgetSearchQuery = ref('');

const itemEntryMode = ref<'select' | 'manual'>('select');

const normalizeSourceType = (sourceType?: null | string) =>
    String(sourceType ?? '').toLowerCase();

const syncBudgetType = (sourceType?: null | string) => {
    const normalized = normalizeSourceType(sourceType);
    if (normalized === 'rab' || normalized === 'rap') {
        activeBudgetType.value = normalized as 'rab' | 'rap';
    }
};

const availableBudgetTypes = computed(() => {
    const types = new Set(
        props.budgetItemOptions.map((item) =>
            normalizeSourceType(item.sourceType ?? (item as any).source_type),
        ),
    );

    return ['rab', 'rap'].filter((type) => types.has(type)) as Array<
        'rab' | 'rap'
    >;
});

const syncActiveBudgetType = (preferred?: null | string) => {
    if (preferred === 'rab' || preferred === 'rap') {
        activeBudgetType.value = preferred;
        return;
    }

    activeBudgetType.value =
        availableBudgetTypes.value[0] ?? activeBudgetType.value;
};

const getOptionValue = (row: any) => {
    if (!row) return '';

    if (row.value !== undefined && row.value !== null) {
        return String(row.value);
    }

    if (row.id !== undefined && row.id !== null) {
        return String(row.id);
    }

    if (row.sourceType && row.sourceItemId) {
        return `${row.sourceType}:${row.sourceItemId}`;
    }

    return JSON.stringify(row);
};

const isBudgetSelected = (row: any) =>
    selectedBudgetValues.value.includes(getOptionValue(row));

const toggleBudgetSelection = (row: any, checked: boolean) => {
    const optionValue = getOptionValue(row);

    if (!optionValue) {
        return;
    }

    syncBudgetType(row?.sourceType);

    if (checked) {
        if (!selectedBudgetValues.value.includes(optionValue)) {
            selectedBudgetValues.value.push(optionValue);
        }

        return;
    }

    selectedBudgetValues.value = selectedBudgetValues.value.filter(
        (value) => value !== optionValue,
    );
};

const budgetTypeCounts = computed(() => ({
    rab: props.budgetItemOptions.filter(
        (item) => normalizeSourceType(item.sourceType) === 'rab',
    ).length,
    rap: props.budgetItemOptions.filter(
        (item) => normalizeSourceType(item.sourceType) === 'rap',
    ).length,
}));

const filteredBudgetOptions = computed(() =>
    props.budgetItemOptions.filter(
        (item) =>
            normalizeSourceType(item.sourceType ?? (item as any).source_type) ===
            activeBudgetType.value,
    ),
);

const visibleBudgetOptions = computed(() => {
    const query = budgetSearchQuery.value.trim().toLowerCase();

    if (!query) {
        return filteredBudgetOptions.value;
    }

    return filteredBudgetOptions.value.filter((item) =>
        [
            item.label,
            item.hint,
            item.category,
            item.description,
            item.unit,
            item.documentNumber,
            item.documentDate,
        ]
            .filter(Boolean)
            .some((value) => String(value).toLowerCase().includes(query)),
    );
});

const existingBudgetKeys = computed(() => {
    return props.items
        .filter(
            (item) =>
                item.sourceType &&
                item.sourceItemId,
        )
        .map(
            (item) =>
                `${item.sourceType}:${item.sourceItemId}`,
        );
});

const existingBudgetMap = computed(() => {
    const map = new Map<string, FinancialItem>();

    props.items.forEach((item) => {
        if (item.sourceType && item.sourceItemId) {
            map.set(
                `${item.sourceType}:${item.sourceItemId}`,
                item,
            );
        }
    });

    return map;
});

const isExistingBudgetItem = (
    option: BudgetItemOption,
) => {
    return existingBudgetKeys.value.includes(
        `${option.sourceType}:${option.sourceItemId}`,
    );
};

const allVisibleSelected = computed(() => {
    return (
        visibleBudgetOptions.value.length > 0 &&
        visibleBudgetOptions.value.every((option) =>
            selectedBudgetValues.value.includes(getOptionValue(option)),
        )
    );
});

const toggleAllVisible = (checked: boolean) => {
    const visibleValues = visibleBudgetOptions.value.map((option) =>
        getOptionValue(option),
    );

    if (checked) {
        selectedBudgetValues.value = [
            ...new Set([
                ...selectedBudgetValues.value,
                ...visibleValues,
            ]),
        ];
        return;
    }

    selectedBudgetValues.value = selectedBudgetValues.value.filter(
        (value) => !visibleValues.includes(value),
    );
};

const clearBudgetSelection = () => {
    selectedBudgetValues.value = [];
};

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
    editingItemId.value === null ? 'Tambah baris item' : 'Edit item manual',
);

const itemTotal = computed(
    () => Number(itemForm.quantity || 0) * Number(itemForm.unit_price || 0),
);

const backToList = () => router.get(props.indexUrl);
const refreshPage = () => router.reload();

const submitHeader = () => {
    headerForm.patch(props.updateUrl, {
        preserveScroll: true,
        onSuccess: refreshPage,
    });
};

const resetItemForm = () => {
    editingItemId.value = null;
    selectedBudgetValues.value = [];
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

    selectedBudgetValues.value =
        props.budgetItemOptions
            .filter((option) =>
                isExistingBudgetItem(option),
            )
            .map((option) =>
                getOptionValue(option),
            );

    syncActiveBudgetType();

    itemEntryMode.value =
        availableBudgetTypes.value.length > 0
            ? 'select'
            : 'manual';

    isItemOpen.value = true;
};

const openEditItem = (item: FinancialItem) => {
    editingItemId.value = item.id;
    selectedBudgetValues.value = [];
    syncBudgetType(item.sourceType);
    syncActiveBudgetType(item.sourceType);

    // Force manual mode for direct edits
    itemEntryMode.value = 'manual';

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

watch(
    () => props.budgetItemOptions,
    () => {
        syncActiveBudgetType(activeBudgetType.value);
    },
    { immediate: true, deep: true },
);

const closeItemModal = () => {
    isItemOpen.value = false;
    resetItemForm();
    isBulkSubmitting.value = false;
};

const selectedBudgetOptions = computed(() =>
    props.budgetItemOptions.filter((item) =>
        selectedBudgetValues.value.includes(getOptionValue(item)),
    ),
);

const submitSelectedBudgetItems = async () => {
    isBulkSubmitting.value = true;

    try {
        const selectedKeys = new Set(
            selectedBudgetOptions.value.map(
                (option) =>
                    `${option.sourceType}:${option.sourceItemId}`,
            ),
        );

        const existingKeys = new Set(
            existingBudgetMap.value.keys(),
        );

        const itemsToCreate = selectedBudgetOptions.value.filter(
            (option) =>
                !existingKeys.has(
                    `${option.sourceType}:${option.sourceItemId}`,
                ),
        );

        const itemsToDelete = Array.from(
            existingBudgetMap.value.entries(),
        )
            .filter(([key]) => !selectedKeys.has(key))
            .map(([, item]) => item);

        for (const item of itemsToDelete) {
            const response = await csrfFetch(
                `${props.itemUpdateUrlBase}/${item.id}`,
                {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                    },
                },
            );

            if (!response.ok) {
                throw new Error(
                    `Gagal menghapus item ${item.description ?? item.id}`,
                );
            }
        }

        for (const option of itemsToCreate) {
            const response = await csrfFetch(
                props.itemStoreUrl,
                {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type':
                            'application/json',
                    },
                    body: JSON.stringify({
                        source_type:
                            option.sourceType ??
                            'manual',
                        source_item_id:
                            option.sourceItemId
                                ? String(
                                      option.sourceItemId,
                                  )
                                : '',
                        category:
                            option.category ??
                            option.label ??
                            '',
                        description:
                            option.description ??
                            option.label ??
                            '',
                        unit:
                            option.unit ??
                            'ls',
                        quantity:
                            option.quantity > 0
                                ? option.quantity
                                : 1,
                        unit_price:
                            option.totalBudget ??
                            option.totalPrice ??
                            0,
                        total_price:
                            option.totalBudget ??
                            option.totalPrice ??
                            0,
                        vendor: '',
                        notes: '',
                    }),
                },
            );

            if (!response.ok) {
                const payload =
                    await response
                        .json()
                        .catch(() => ({}));

                throw new Error(
                    payload.message ??
                        `Gagal menyimpan ${option.label}`,
                );
            }
        }

        closeItemModal();
        refreshPage();
    } catch (error) {
        window.alert(
            error instanceof Error
                ? error.message
                : 'Gagal sinkronisasi item.',
        );
    } finally {
        isBulkSubmitting.value = false;
    }
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
                    <DocumentList
                        class="mt-2"
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
                class="flex h-[min(90dvh,48rem)] w-[calc(100vw-2rem)] !max-w-5xl flex-col overflow-hidden p-0"
            >
                <DialogHeader class="shrink-0 border-b border-sidebar-border/70 px-5 py-4 sm:px-6">
                    <DialogTitle>{{ itemDialogTitle }}</DialogTitle>
                    <DialogDescription v-if="editingItemId === null">
                        Pilih item dari data proyek atau buat baris manual baru.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="editingItemId === null"
                    class="flex shrink-0 gap-2 border-b border-sidebar-border/70 px-5 py-3 sm:px-6"
                >
                    <Button
                        type="button"
                        :variant="itemEntryMode === 'select' ? 'default' : 'outline'"
                        @click="itemEntryMode = 'select'"
                    >
                        Pilih dari RAB / RAP
                    </Button>
                    <Button
                        type="button"
                        :variant="itemEntryMode === 'manual' ? 'default' : 'outline'"
                        @click="itemEntryMode = 'manual'"
                    >
                        Input Manual
                    </Button>
                </div>

                <div
                    v-if="itemEntryMode === 'select' && editingItemId === null"
                    class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden px-5 py-4 sm:px-6"
                >
                    <div class="grid gap-3 lg:grid-cols-[auto_minmax(16rem,1fr)_auto] lg:items-center">
                        <div class="inline-flex rounded-md border border-sidebar-border/70 bg-muted/30 p-1">
                            <button
                                type="button"
                                class="rounded px-3 py-2 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
                                :class="activeBudgetType === 'rab' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                                :disabled="!availableBudgetTypes.includes('rab')"
                                @click="activeBudgetType = 'rab'"
                            >
                                RAB
                                <span class="ml-1 text-xs text-muted-foreground">
                                    {{ budgetTypeCounts.rab }}
                                </span>
                            </button>
                            <button
                                type="button"
                                class="rounded px-3 py-2 text-sm font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
                                :class="activeBudgetType === 'rap' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'"
                                :disabled="!availableBudgetTypes.includes('rap')"
                                @click="activeBudgetType = 'rap'"
                            >
                                RAP
                                <span class="ml-1 text-xs text-muted-foreground">
                                    {{ budgetTypeCounts.rap }}
                                </span>
                            </button>
                        </div>

                        <div class="relative min-w-0">
                            <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="budgetSearchQuery"
                                class="h-10 pl-9"
                                placeholder="Cari item, kategori, dokumen..."
                            />
                        </div>

                        <div class="flex items-center justify-between gap-3 text-sm lg:justify-end">
                            <span class="font-medium text-muted-foreground">
                                {{ selectedBudgetValues.length }} dipilih
                            </span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                :disabled="selectedBudgetValues.length === 0"
                                @click="clearBudgetSelection"
                            >
                                Bersihkan
                            </Button>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-auto rounded-md border border-sidebar-border/70">
                        <div class="grid min-w-[51rem] grid-cols-[3rem_minmax(18rem,1.4fr)_minmax(12rem,1fr)_8rem_10rem] border-b border-sidebar-border/70 bg-muted/60 text-xs font-medium text-muted-foreground">
                            <div class="flex items-center justify-center px-4 py-3">
                                <input
                                    type="checkbox"
                                    class="size-4 cursor-pointer"
                                    :checked="allVisibleSelected"
                                    @change="
                                        toggleAllVisible(
                                            ($event.target as HTMLInputElement).checked
                                        )
                                    "
                                />
                            </div>
                            <div class="px-4 py-3">Dokumen / Item</div>
                            <div class="px-4 py-3">Kategori</div>
                            <div class="px-4 py-3 text-right">Qty</div>
                            <div class="px-4 py-3 text-right">Total</div>
                        </div>

                        <div class="min-w-[51rem] pb-12">
                            <button
                                v-for="option in visibleBudgetOptions"
                                :key="getOptionValue(option)"
                                type="button"
                                class="grid w-full grid-cols-[3rem_minmax(18rem,1.4fr)_minmax(12rem,1fr)_8rem_10rem] border-b border-sidebar-border/60 text-left text-sm transition hover:bg-muted/40"
                                :class="isBudgetSelected(option) ? 'bg-primary/10' : 'bg-background'"
                                @click="toggleBudgetSelection(option, !isBudgetSelected(option))"
                            >
                                <div class="flex items-center justify-center px-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="size-4 rounded border-sidebar-border accent-primary"
                                        :checked="isBudgetSelected(option)"
                                        @click.stop
                                        @change="toggleBudgetSelection(option, ($event.target as HTMLInputElement).checked)"
                                    />
                                </div>
                                <div class="min-w-0 px-4 py-3">
                                    <p class="truncate font-medium text-foreground">
                                        {{ option.label }}
                                    </p>

                                    <p
                                        v-if="isExistingBudgetItem(option)"
                                        class="mt-1 text-xs font-medium text-emerald-500"
                                    >
                                        Sudah ditambahkan
                                    </p>
                                    <p class="mt-1 truncate text-xs text-muted-foreground">
                                        {{ option.description || option.hint || '-' }}
                                    </p>
                                </div>
                                <div class="min-w-0 px-4 py-3 text-muted-foreground">
                                    <p class="truncate">
                                        {{ option.category || '-' }}
                                    </p>
                                    <p class="mt-1 truncate text-xs">
                                        {{ option.unit || '-' }}
                                    </p>
                                </div>
                                <div class="px-4 py-3 text-right tabular-nums">
                                    {{ option.quantity || 0 }}
                                </div>
                                <div class="px-4 py-3 text-right font-medium tabular-nums">
                                    {{ formatCurrency(option.totalBudget ?? option.totalPrice ?? 0) }}
                                </div>
                            </button>

                            <div
                                v-if="visibleBudgetOptions.length === 0"
                                class="px-4 py-10 text-center text-sm text-muted-foreground"
                            >
                                Tidak ada item yang cocok.
                            </div>
                        </div>
                    </div>
                </div>

                <form
                    v-else
                    class="grid min-h-0 min-w-0 flex-1 gap-4 overflow-x-hidden overflow-y-auto px-5 py-4 sm:grid-cols-2 sm:px-6"
                    @submit.prevent="submitItem"
                >
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
                </form>

                <DialogFooter class="shrink-0 border-t border-sidebar-border/70 bg-background px-5 py-4 sm:px-6">
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeItemModal"
                    >
                        Batal
                    </Button>

                    <Button
                        v-if="itemEntryMode === 'select' && editingItemId === null"
                        type="button"
                        :disabled="selectedBudgetValues.length === 0 || isBulkSubmitting"
                        @click="submitSelectedBudgetItems"
                    >
                        <LoaderCircle v-if="isBulkSubmitting" class="mr-2 size-4 animate-spin" />
                        Tambahkan Terpilih ({{ selectedBudgetValues.length }})
                    </Button>

                    <Button
                        v-else
                        type="button"
                        :disabled="itemForm.processing"
                        @click="submitItem"
                    >
                        {{ editingItemId === null ? 'Simpan' : 'Update' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
