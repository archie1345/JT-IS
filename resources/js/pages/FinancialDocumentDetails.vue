<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ArrowLeft,
    FileText,
    Pencil,
    Plus,
    Printer,
    Save,
    Trash2,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import EntityDetailHero from '@/components/entity/EntityDetailHero.vue';
import EntityMetricCard from '@/components/entity/EntityMetricCard.vue';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import InputError from '@/components/InputError.vue';
import ProjectDocumentUploadPanel from '@/components/ProjectDocumentUploadPanel.vue';
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
const isPdfOpen = ref(false);
const editingItemId = ref<null | number>(null);
const selectedSource = ref('');
const deletingItemId = ref<null | number>(null);

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

const formatCurrency = (value: number | string | null | undefined) =>
    value === null || value === undefined || value === ''
        ? '-'
        : new Intl.NumberFormat('id-ID', {
              style: 'currency',
              currency: 'IDR',
              maximumFractionDigits: 0,
          }).format(Number(value));

const documentTitle = computed(() =>
    String(
        props.record.invoice_number ||
            props.record.reference_number ||
            `${props.recordLabel} #${props.record.id}`,
    ),
);

const itemDialogTitle = computed(() =>
    editingItemId.value === null ? 'Add item' : 'Edit item',
);

const itemTotal = computed(
    () => Number(itemForm.quantity || 0) * Number(itemForm.unit_price || 0),
);

const backToList = () => router.get(props.indexUrl);
const refreshPage = () => router.reload({ preserveScroll: true });

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
    if (!window.confirm('Delete this item?')) return;

    deletingItemId.value = item.id;
    router.delete(`${props.itemUpdateUrlBase}/${item.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingItemId.value = null;
        },
        onSuccess: refreshPage,
    });
};

const printInvoice = () => window.print();
</script>

<template>
    <Head :title="`${props.title} #${props.record.id}`" />

    <AppLayout :breadcrumbs="props.breadcrumbs">
        <div
            class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-3 rounded-xl p-2 sm:gap-4 sm:p-4"
        >
            <EntityDetailHero
                back-label="Back"
                :title="String(props.record.project_name || 'Project')"
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
                        Back
                    </Button>
                </template>
            </EntityDetailHero>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <EntityMetricCard
                    label="Items"
                    :value="props.summary.itemCount"
                />
                <EntityMetricCard
                    label="Subtotal"
                    :value="formatCurrency(props.summary.subtotal)"
                />
                <EntityMetricCard
                    :label="
                        props.kind === 'invoice' ? 'Tax' : 'Linked Budget Items'
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

            <section class="grid gap-4">
                <EntityPageSection
                    title="Record Fields"
                    :description="`Edit the header fields for this ${props.recordLabel.toLowerCase()} document.`"
                >
                    <form
                        class="grid gap-4 sm:grid-cols-2"
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
                                @click="isPdfOpen = true"
                            >
                                <FileText class="mr-2 size-4" />
                                Make PDF
                            </Button>
                            <Button
                                type="submit"
                                :disabled="headerForm.processing"
                            >
                                <Save class="mr-2 size-4" />
                                Save Record Fields
                            </Button>
                        </div>
                    </form>
                </EntityPageSection>

                <EntityPageSection
                    title="Uploaded Files"
                    :description="`Files attached to this ${props.recordLabel.toLowerCase()} record.`"
                >
                    <ProjectDocumentUploadPanel
                        :project-id="props.upload.projectId"
                        :component-type="props.upload.componentType"
                        :component-id="props.upload.componentId"
                        :documents="props.upload.documents"
                        :title="`${props.recordLabel} files`"
                        :description="`Attach invoices, receipts, approvals, and supporting documents.`"
                    />
                </EntityPageSection>

                <EntityPageSection
                    title="Item Details"
                    :description="`Break this ${props.recordLabel.toLowerCase()} into billable or realized line items.`"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 px-3 py-3 sm:px-5 sm:py-4"
                    >
                        <p class="text-sm text-muted-foreground">
                            Pick from this project's RAB/RAP items or add a
                            manual line.
                        </p>
                        <Button @click="openCreateItem">
                            <Plus class="mr-2 size-4" />
                            Add row
                        </Button>
                    </div>

                    <div class="table-scrollbar overflow-x-scroll">
                        <table class="min-w-[72rem] text-sm">
                            <thead
                                class="bg-muted/40 text-left text-muted-foreground"
                            >
                                <tr>
                                    <th class="px-4 py-3 font-medium">
                                        Source
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Description
                                    </th>
                                    <th
                                        v-if="props.kind === 'cost'"
                                        class="px-4 py-3 font-medium"
                                    >
                                        Vendor
                                    </th>
                                    <th class="px-4 py-3 font-medium">Qty</th>
                                    <th class="px-4 py-3 font-medium">Unit</th>
                                    <th class="px-4 py-3 font-medium">
                                        Unit Price
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Amount
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right font-medium"
                                    >
                                        Action
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
                                        class="px-4 py-3 text-muted-foreground uppercase"
                                    >
                                        {{ item.sourceType || 'manual' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <p
                                            v-if="item.category"
                                            class="mb-1 text-xs text-muted-foreground"
                                        >
                                            {{ item.category }}
                                        </p>
                                        {{ item.description || '-' }}
                                    </td>
                                    <td
                                        v-if="props.kind === 'cost'"
                                        class="px-4 py-3"
                                    >
                                        {{ item.vendor || '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ item.quantity }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ item.unit || '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ formatCurrency(item.unitPrice) }}
                                    </td>
                                    <td class="px-4 py-3 font-medium">
                                        {{ formatCurrency(item.totalPrice) }}
                                    </td>
                                    <td class="px-4 py-3">
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
                                                    deletingItemId === item.id
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
                                        :colspan="props.kind === 'cost' ? 8 : 7"
                                        class="px-4 py-8 text-center text-sm text-muted-foreground"
                                    >
                                        No items yet. Add rows from the
                                        project's RAB/RAP to start
                                        reconciliation.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </EntityPageSection>
            </section>
        </div>

        <Dialog v-model:open="isItemOpen">
            <DialogContent
                class="max-h-[calc(100vh-2rem)] w-[calc(100vw-2rem)] overflow-y-auto sm:max-w-2xl"
            >
                <DialogHeader>
                    <DialogTitle>{{ itemDialogTitle }}</DialogTitle>
                    <DialogDescription>
                        Link a project budget item or enter a manual line.
                    </DialogDescription>
                </DialogHeader>

                <form
                    class="grid gap-4 py-2 sm:grid-cols-2"
                    @submit.prevent="submitItem"
                >
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="source_item">Pick RAB/RAP Item</Label>
                        <select
                            id="source_item"
                            v-model="selectedSource"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            @change="applyBudgetItem"
                        >
                            <option value="">Manual item</option>
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

                    <div class="space-y-2">
                        <Label for="category">Category</Label>
                        <Input id="category" v-model="itemForm.category" />
                        <InputError :message="itemForm.errors.category" />
                    </div>
                    <div v-if="props.kind === 'cost'" class="space-y-2">
                        <Label for="vendor">Vendor / Payee</Label>
                        <Input id="vendor" v-model="itemForm.vendor" />
                        <InputError :message="itemForm.errors.vendor" />
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="description">Description</Label>
                        <Input
                            id="description"
                            v-model="itemForm.description"
                        />
                        <InputError :message="itemForm.errors.description" />
                    </div>
                    <div class="space-y-2">
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
                    <div class="space-y-2">
                        <Label for="unit">Unit</Label>
                        <Input id="unit" v-model="itemForm.unit" />
                        <InputError :message="itemForm.errors.unit" />
                    </div>
                    <div class="space-y-2">
                        <Label for="unit_price">Unit Price</Label>
                        <Input
                            id="unit_price"
                            v-model.number="itemForm.unit_price"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <InputError :message="itemForm.errors.unit_price" />
                    </div>
                    <div class="space-y-2">
                        <Label for="total_price">Total Price</Label>
                        <Input
                            id="total_price"
                            v-model.number="itemForm.total_price"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <p class="text-xs text-muted-foreground">
                            Computed: {{ formatCurrency(itemTotal) }}
                        </p>
                        <InputError :message="itemForm.errors.total_price" />
                    </div>
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="notes">Notes</Label>
                        <textarea
                            id="notes"
                            v-model="itemForm.notes"
                            class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                        />
                        <InputError :message="itemForm.errors.notes" />
                    </div>

                    <DialogFooter class="sm:col-span-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeItemModal"
                            >Cancel</Button
                        >
                        <Button type="submit" :disabled="itemForm.processing">
                            {{ editingItemId === null ? 'Save' : 'Update' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-if="props.kind === 'invoice'" v-model:open="isPdfOpen">
            <DialogContent
                class="max-h-[calc(100vh-2rem)] w-[calc(100vw-2rem)] overflow-y-auto sm:max-w-5xl"
            >
                <DialogHeader class="no-print">
                    <DialogTitle>Invoice PDF</DialogTitle>
                    <DialogDescription
                        >Preview this billing record, then print or save it as
                        PDF.</DialogDescription
                    >
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
                        class="invoice-sheet mx-auto flex min-h-[297mm] w-[210mm] flex-col bg-white text-[#111827] shadow-lg"
                    >
                        <div class="bg-[#0f766e] px-10 py-8 text-white">
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
                                        {{ documentTitle }}
                                    </p>
                                    <p class="mt-1">
                                        Date:
                                        {{ headerForm.invoice_date || '-' }}
                                    </p>
                                    <p>Due: {{ headerForm.due_date || '-' }}</p>
                                </div>
                            </div>
                        </div>
                        <div
                            class="flex flex-1 flex-col gap-8 px-10 py-8 text-sm"
                        >
                            <div>
                                <p
                                    class="text-xs font-medium uppercase opacity-60"
                                >
                                    Bill To
                                </p>
                                <p class="mt-2 text-lg font-semibold">
                                    {{ props.record.client_name || '-' }}
                                </p>
                                <p class="mt-1 opacity-75">
                                    {{
                                        props.record.project_name ||
                                        'Project billing'
                                    }}
                                </p>
                            </div>
                            <table class="w-full border-collapse">
                                <thead class="bg-[#0f766e] text-white">
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
                                    <tr
                                        v-for="item in props.items"
                                        :key="item.id"
                                        class="border-b"
                                    >
                                        <td class="px-4 py-3">
                                            {{ item.description || '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            {{
                                                formatCurrency(item.totalPrice)
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="ml-auto w-72 space-y-2">
                                <div class="flex justify-between">
                                    <span>Subtotal</span
                                    ><span>{{
                                        formatCurrency(props.summary.subtotal)
                                    }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Tax</span
                                    ><span>{{
                                        formatCurrency(props.summary.tax)
                                    }}</span>
                                </div>
                                <div
                                    class="flex justify-between border-t pt-3 text-base font-semibold"
                                >
                                    <span>Total</span
                                    ><span>{{
                                        formatCurrency(props.summary.total)
                                    }}</span>
                                </div>
                            </div>
                            <div class="mt-auto flex justify-end pt-8">
                                <div class="w-48 text-center">
                                    <div class="mb-16 border-t"></div>
                                    <p class="font-medium">
                                        Authorized Signature
                                    </p>
                                </div>
                            </div>
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
