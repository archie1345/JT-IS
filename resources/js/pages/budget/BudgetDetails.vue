<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { ArrowLeft, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import EntityDetailHero from '@/components/entity/EntityDetailHero.vue';
import EntityMetricCard from '@/components/entity/EntityMetricCard.vue';
import EntityPageSection from '@/components/entity/EntityPageSection.vue';
import DocumentUploadPanel from '@/components/shared/DocumentUploadPanel.vue';
import type { BreadcrumbItem } from '@/types';
import type { UploadedDocument } from '@/types/project';

type DetailItem = {
    id: number;
    category: string | null;
    subCategory: string | null;
    description: string;
    quantity: number;
    unit: string;
    unitPrice: number;
    totalPrice: number;
    specBrand: string | null;
    specSize: string | null;
    specStrength: string | null;
};

const props = defineProps<{
    kind: 'rab' | 'rap';
    title: string;
    recordLabel: string;
    record: {
        id: number;
        projectId: number;
        projectName: string;
        document_number: string | null;
        document_date: string | null;
        totalBudget: number;
        notes: string | null;
        itemCount: number;
        createdAt: string | null;
        updatedAt: string | null;
    };
    items: DetailItem[];
    uploadedDocuments: UploadedDocument[];
    summary: {
        subtotal: number;
        itemCount: number;
        difference: number;
    };
}>();

const isOpen = ref(false);
const editingItemId = ref<null | number>(null);
const deletingItemId = ref<null | number>(null);

const headerForm = useForm({
    project_id: props.record.projectId,
    document_number: props.record.document_number ?? '',
    document_date: props.record.document_date ?? '',
    total_budget: props.record.totalBudget ?? 0,
    notes: props.record.notes ?? '',
});

const form = useForm({
    category: '',
    sub_category: '',
    description: '',
    unit: '',
    quantity: 0,
    unit_price: 0,
    total_price: 0,
    spec_brand: '',
    spec_size: '',
    spec_strength: '',
});

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: props.recordLabel,
        href: props.kind === 'rab' ? '/rabs' : '/raps',
    },
    {
        title: `${props.recordLabel} #${props.record.id}`,
        href:
            props.kind === 'rab'
                ? `/rabs/${props.record.id}`
                : `/raps/${props.record.id}`,
    },
]);

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const backToList = () => {
    router.get(props.kind === 'rab' ? '/rabs' : '/raps');
};

const refreshPage = () => {
    router.reload();
};

const recordUpdateUrl = computed(() =>
    props.kind === 'rab'
        ? `/rabs/${props.record.id}`
        : `/raps/${props.record.id}`,
);
const itemStoreUrl = computed(() =>
    props.kind === 'rab'
        ? `/rabs/${props.record.id}/items`
        : `/raps/${props.record.id}/items`,
);
const itemUpdateUrl = (id: number) =>
    props.kind === 'rab' ? `/rab-items/${id}` : `/rap-items/${id}`;

const resetForm = () => {
    editingItemId.value = null;
    form.defaults({
        category: '',
        sub_category: '',
        description: '',
        unit: '',
        quantity: 0,
        unit_price: 0,
        total_price: 0,
        spec_brand: '',
        spec_size: '',
        spec_strength: '',
    });
    form.reset();
    form.clearErrors();
};

const openCreate = () => {
    resetForm();
    isOpen.value = true;
};

const openEdit = (item: DetailItem) => {
    editingItemId.value = item.id;
    const payload = {
        category: item.category ?? '',
        sub_category: item.subCategory ?? '',
        description: item.description ?? '',
        unit: item.unit === '-' ? '' : item.unit,
        quantity: item.quantity ?? 0,
        unit_price: item.unitPrice ?? 0,
        total_price: item.totalPrice ?? 0,
        spec_brand: item.specBrand ?? '',
        spec_size: item.specSize ?? '',
        spec_strength: item.specStrength ?? '',
    };

    form.defaults(payload);
    form.reset();
    form.clearErrors();
    Object.assign(form, payload);
    isOpen.value = true;
};

const closeModal = () => {
    isOpen.value = false;
    resetForm();
};

const submitItem = () => {
    const parentKey = props.kind === 'rab' ? 'rab_id' : 'rap_id';
    const payload = {
        ...form.data(),
        [parentKey]: props.record.id,
    };

    if (editingItemId.value === null) {
        router.post(itemStoreUrl.value, payload, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                refreshPage();
            },
        });
        return;
    }

    router.patch(itemUpdateUrl(editingItemId.value), payload, {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            refreshPage();
        },
    });
};

const submitHeader = () => {
    headerForm.patch(recordUpdateUrl.value, {
        preserveScroll: true,
        onSuccess: refreshPage,
    });
};

const destroyItem = (item: DetailItem) => {
    if (!window.confirm('Delete this item?')) {
        return;
    }

    deletingItemId.value = item.id;
    router.delete(itemUpdateUrl(item.id), {
        preserveScroll: true,
        onFinish: () => {
            deletingItemId.value = null;
        },
        onSuccess: refreshPage,
    });
};

const itemDialogTitle = computed(() =>
    editingItemId.value === null ? 'Add item' : 'Edit item',
);
</script>

<template>
    <Head :title="`${props.title} #${props.record.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-3 rounded-xl p-2 sm:gap-4 sm:p-4"
        >
            <EntityDetailHero
                back-label="Back"
                :title="props.record.projectName"
                :description="`${props.recordLabel} #${props.record.id}`"
                :badge-text="props.recordLabel"
                badge-class="bg-blue-500/15 text-blue-600 ring-1 ring-blue-500/25"
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
                    label="Budget"
                    :value="formatCurrency(props.record.totalBudget)"
                />
                <EntityMetricCard
                    label="Difference"
                    :value="formatCurrency(props.summary.difference)"
                />
            </section>

            <section class="grid gap-4">
                <EntityPageSection
                    title="Record Fields"
                    :description="`Edit the header fields captured from this ${props.recordLabel} document.`"
                >
                    <form
                        class="grid gap-4 sm:grid-cols-2"
                        @submit.prevent="submitHeader"
                    >
                        <div class="space-y-2">
                            <Label for="document_number">Document Number</Label>
                            <Input
                                id="document_number"
                                v-model="headerForm.document_number"
                                placeholder="Document or contract number"
                            />
                            <InputError
                                :message="headerForm.errors.document_number"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="document_date">Document Date</Label>
                            <Input
                                id="document_date"
                                v-model="headerForm.document_date"
                                type="date"
                            />
                            <InputError
                                :message="headerForm.errors.document_date"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="total_budget">
                                {{
                                    props.kind === 'rab'
                                        ? 'Total / Contract Value'
                                        : 'Total Execution Budget'
                                }}
                            </Label>
                            <Input
                                id="total_budget"
                                v-model="headerForm.total_budget"
                                type="number"
                                min="0"
                                step="0.01"
                                readonly
                                class="bg-muted/30"
                            />
                            <InputError
                                :message="headerForm.errors.total_budget"
                            />
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <Label for="notes">Notes</Label>
                            <textarea
                                id="notes"
                                v-model="headerForm.notes"
                                class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                placeholder="Terbilang, deviation, or remarks"
                            />
                            <InputError :message="headerForm.errors.notes" />
                        </div>

                        <div class="flex justify-end sm:col-span-2">
                            <Button
                                type="submit"
                                :disabled="headerForm.processing"
                            >
                                Save Record Fields
                            </Button>
                        </div>
                    </form>
                </EntityPageSection>

                <EntityPageSection
                    title="Uploaded Files"
                    :description="`Files attached to this ${props.recordLabel}.`"
                >
                    <DocumentUploadPanel
                        :project-id="props.record.projectId"
                        :component-type="props.kind"
                        :component-id="props.record.id"
                        :documents="props.uploadedDocuments"
                        :title="`${props.recordLabel} files`"
                        :description="`Upload source documents, approvals, and supporting files for this ${props.recordLabel}.`"
                    />
                </EntityPageSection>

                <EntityPageSection
                    title="Item Details"
                    :description="`Detail list for ${props.record.projectName}.`"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 px-3 py-3 sm:px-5 sm:py-4"
                    >
                        <div class="flex flex-wrap gap-2">
                            <Button @click="openCreate">
                                <Plus class="mr-2 size-4" />
                                Add row
                            </Button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[56rem] text-sm">
                            <thead
                                class="bg-muted/40 text-left text-muted-foreground"
                            >
                                <tr>
                                    <th class="px-4 py-3 font-medium">
                                        Description
                                    </th>
                                    <th class="px-4 py-3 font-medium">
                                        Quantity
                                    </th>
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
                                    <td class="px-4 py-3">
                                        <p
                                            v-if="
                                                item.category ||
                                                item.subCategory
                                            "
                                            class="mb-1 text-xs text-muted-foreground"
                                        >
                                            {{
                                                [
                                                    item.category,
                                                    item.subCategory,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' / ')
                                            }}
                                        </p>
                                        <Input
                                            :model-value="item.description"
                                            disabled
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <Input
                                                :model-value="item.quantity"
                                                type="number"
                                                disabled
                                                class="w-24"
                                            />
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Input
                                            :model-value="item.unit"
                                            class="w-24"
                                            disabled
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <Input
                                            :model-value="item.unitPrice"
                                            type="number"
                                            disabled
                                            class="min-w-32"
                                        />
                                    </td>
                                    <td
                                        class="px-4 py-3 font-medium text-foreground"
                                    >
                                        {{ formatCurrency(item.totalPrice) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                @click="openEdit(item)"
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
                                        colspan="6"
                                        class="px-4 py-8 text-center text-sm text-muted-foreground"
                                    >
                                        No items yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </EntityPageSection>
            </section>
        </div>

        <Dialog v-model:open="isOpen">
            <DialogContent
                class="max-h-[calc(100vh-2rem)] w-[calc(100vw-2rem)] overflow-y-auto sm:max-w-2xl"
            >
                <DialogHeader>
                    <DialogTitle>{{ itemDialogTitle }}</DialogTitle>
                </DialogHeader>

                <form
                    class="grid gap-4 py-2 sm:grid-cols-2"
                    @submit.prevent="submitItem"
                >
                    <div class="space-y-2">
                        <Label for="category">Category</Label>
                        <Input id="category" v-model="form.category" />
                        <InputError :message="form.errors.category" />
                    </div>

                    <div class="space-y-2">
                        <Label for="sub_category">Sub Category</Label>
                        <Input id="sub_category" v-model="form.sub_category" />
                        <InputError :message="form.errors.sub_category" />
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="description">Description</Label>
                        <Input id="description" v-model="form.description" />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="space-y-2">
                        <Label for="unit">Unit</Label>
                        <Input id="unit" v-model="form.unit" />
                        <InputError :message="form.errors.unit" />
                    </div>

                    <div class="space-y-2">
                        <Label for="quantity">Quantity</Label>
                        <Input
                            id="quantity"
                            v-model.number="form.quantity"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <InputError :message="form.errors.quantity" />
                    </div>

                    <div class="space-y-2">
                        <Label for="unit_price">Unit Price</Label>
                        <Input
                            id="unit_price"
                            v-model.number="form.unit_price"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <InputError :message="form.errors.unit_price" />
                    </div>

                    <div class="space-y-2">
                        <Label for="total_price">Total Price</Label>
                        <Input
                            id="total_price"
                            v-model.number="form.total_price"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <InputError :message="form.errors.total_price" />
                    </div>

                    <template v-if="props.kind === 'rap'">
                        <div class="space-y-2">
                            <Label for="spec_brand">Brand</Label>
                            <Input id="spec_brand" v-model="form.spec_brand" />
                            <InputError :message="form.errors.spec_brand" />
                        </div>

                        <div class="space-y-2">
                            <Label for="spec_size">Size</Label>
                            <Input id="spec_size" v-model="form.spec_size" />
                            <InputError :message="form.errors.spec_size" />
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <Label for="spec_strength">Strength</Label>
                            <Input
                                id="spec_strength"
                                v-model="form.spec_strength"
                            />
                            <InputError :message="form.errors.spec_strength" />
                        </div>
                    </template>

                    <DialogFooter class="sm:col-span-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeModal"
                            >Cancel</Button
                        >
                        <Button type="submit" :disabled="form.processing">
                            {{ editingItemId === null ? 'Save' : 'Update' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
