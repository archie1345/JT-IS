<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { ArrowLeft, ChevronRight, Pencil, Plus, Trash2 } from 'lucide-vue-next';
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
const expandedGroups = ref<Record<string, boolean>>({});

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

const groupedItems = computed(() => {
    const groups = new Map<
        string,
        {
            key: string;
            category: string;
            subCategory: null | string;
            items: DetailItem[];
            total: number;
        }
    >();

    props.items.forEach((item) => {
        const category = item.category?.trim() || 'Tanpa kategori';
        const subCategory = item.subCategory?.trim() || null;
        const key = `${category}::${subCategory ?? ''}`;
        const group = groups.get(key) ?? {
            key,
            category,
            subCategory,
            items: [],
            total: 0,
        };

        group.items.push(item);
        group.total += Number(item.totalPrice ?? 0);
        groups.set(key, group);
    });

    return Array.from(groups.values());
});

const isGroupExpanded = (key: string) => expandedGroups.value[key] !== false;

const toggleGroup = (key: string) => {
    expandedGroups.value = {
        ...expandedGroups.value,
        [key]: !isGroupExpanded(key),
    };
};

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
    if (!window.confirm('Hapus item ini?')) {
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
    editingItemId.value === null ? 'Tambah item' : 'Edit item',
);
</script>

<template>
    <Head :title="`${props.title} #${props.record.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex min-h-[calc(100vh-8rem)] min-w-0 flex-1 flex-col gap-3 rounded-xl p-2 sm:gap-4 sm:p-4"
        >
            <EntityDetailHero
                back-label="Kembali"
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
                    label="Budget"
                    :value="formatCurrency(props.record.totalBudget)"
                />
                <EntityMetricCard
                    label="Selisih"
                    :value="formatCurrency(props.summary.difference)"
                />
            </section>

            <section class="grid min-w-0 gap-4">
                <EntityPageSection
                    title="Field Data"
                    :description="`Edit field header yang diambil dari dokumen ${props.recordLabel}.`"
                >
                    <form
                        class="grid min-w-0 gap-4 sm:grid-cols-2"
                        @submit.prevent="submitHeader"
                    >
                        <div class="min-w-0 space-y-2">
                            <Label for="document_number">Nomor Dokumen</Label>
                            <Input
                                id="document_number"
                                v-model="headerForm.document_number"
                                placeholder="Nomor dokumen atau kontrak"
                            />
                            <InputError
                                :message="headerForm.errors.document_number"
                            />
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="document_date">Tanggal Dokumen</Label>
                            <Input
                                id="document_date"
                                v-model="headerForm.document_date"
                                type="date"
                            />
                            <InputError
                                :message="headerForm.errors.document_date"
                            />
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="total_budget">
                                {{
                                    props.kind === 'rab'
                                        ? 'Total / Nilai Kontrak'
                                        : 'Total Budget Pelaksanaan'
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

                        <div class="min-w-0 space-y-2 sm:col-span-2">
                            <Label for="notes">Catatan</Label>
                            <textarea
                                id="notes"
                                v-model="headerForm.notes"
                                class="flex min-h-28 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                                placeholder="Terbilang, deviasi, atau catatan"
                            />
                            <InputError :message="headerForm.errors.notes" />
                        </div>

                        <div class="flex justify-end sm:col-span-2">
                            <Button
                                type="submit"
                                :disabled="headerForm.processing"
                            >
                                Simpan Field Data
                            </Button>
                        </div>
                    </form>
                </EntityPageSection>

                <EntityPageSection
                    title="File Terunggah"
                    :description="`File yang terlampir ke ${props.recordLabel}.`"
                >
                    <DocumentUploadPanel
                        :project-id="props.record.projectId"
                        :component-type="props.kind"
                        :component-id="props.record.id"
                        :documents="props.uploadedDocuments"
                        :title="`File ${props.recordLabel}`"
                        :description="`Upload dokumen sumber, persetujuan, dan file pendukung untuk ${props.recordLabel}.`"
                    />
                </EntityPageSection>

                <EntityPageSection
                    title="Detail Item"
                    :description="`Daftar detail untuk ${props.record.projectName}.`"
                >
                    <div
                        class="flex min-w-0 flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 px-3 py-3 sm:px-5 sm:py-4"
                    >
                        <div class="flex flex-wrap gap-2">
                            <Button @click="openCreate">
                                <Plus class="mr-2 size-4" />
                                Tambah baris
                            </Button>
                        </div>
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
                                            class="min-w-[16rem] px-3 py-2.5 font-medium sm:min-w-[20rem] sm:px-4 sm:py-3"
                                        >
                                            Deskripsi
                                        </th>
                                        <th
                                            class="min-w-[6rem] px-3 py-2.5 font-medium sm:min-w-[7rem] sm:px-4 sm:py-3"
                                        >
                                            Quantity
                                        </th>
                                        <th
                                            class="min-w-[6rem] px-3 py-2.5 font-medium sm:min-w-[7rem] sm:px-4 sm:py-3"
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
                                    <template
                                        v-for="group in groupedItems"
                                        :key="group.key"
                                    >
                                        <tr
                                            class="border-t border-sidebar-border/60 bg-muted/25"
                                        >
                                            <td
                                                colspan="6"
                                                class="px-3 py-2 sm:px-4"
                                            >
                                                <button
                                                    type="button"
                                                    class="flex w-full items-center justify-between gap-3 text-left"
                                                    @click="
                                                        toggleGroup(group.key)
                                                    "
                                                >
                                                    <span
                                                        class="flex min-w-0 items-center gap-2"
                                                    >
                                                        <ChevronRight
                                                            class="size-4 shrink-0 text-muted-foreground transition-transform"
                                                            :class="{
                                                                'rotate-90':
                                                                    isGroupExpanded(
                                                                        group.key,
                                                                    ),
                                                            }"
                                                        />
                                                        <span class="min-w-0">
                                                            <span
                                                                class="block truncate text-xs font-medium text-foreground sm:text-sm"
                                                            >
                                                                {{
                                                                    group.category
                                                                }}
                                                            </span>
                                                            <span
                                                                v-if="
                                                                    group.subCategory
                                                                "
                                                                class="block truncate text-xs text-muted-foreground"
                                                            >
                                                                {{
                                                                    group.subCategory
                                                                }}
                                                            </span>
                                                        </span>
                                                    </span>
                                                    <span
                                                        class="shrink-0 text-right text-[11px] text-muted-foreground sm:text-xs"
                                                    >
                                                        {{ group.items.length }}
                                                        item
                                                        <span class="mx-1"
                                                            >/</span
                                                        >
                                                        {{
                                                            formatCurrency(
                                                                group.total,
                                                            )
                                                        }}
                                                    </span>
                                                </button>
                                            </td>
                                        </tr>

                                        <tr
                                            v-for="item in group.items"
                                            v-show="isGroupExpanded(group.key)"
                                            :key="item.id"
                                            class="border-t border-sidebar-border/40 align-top"
                                        >
                                            <td class="px-3 py-3 sm:px-4">
                                                <p
                                                    class="font-medium break-words"
                                                >
                                                    {{ item.description }}
                                                </p>
                                            </td>
                                            <td
                                                class="px-3 py-3 tabular-nums sm:px-4"
                                            >
                                                {{ item.quantity }}
                                            </td>
                                            <td class="px-3 py-3 sm:px-4">
                                                {{ item.unit }}
                                            </td>
                                            <td
                                                class="px-3 py-3 tabular-nums sm:px-4"
                                            >
                                                {{
                                                    formatCurrency(
                                                        item.unitPrice,
                                                    )
                                                }}
                                            </td>
                                            <td
                                                class="px-3 py-3 font-medium text-foreground tabular-nums sm:px-4"
                                            >
                                                {{
                                                    formatCurrency(
                                                        item.totalPrice,
                                                    )
                                                }}
                                            </td>
                                            <td class="px-3 py-3 sm:px-4">
                                                <div
                                                    class="flex justify-end gap-1"
                                                >
                                                    <Button
                                                        variant="ghost"
                                                        size="icon-sm"
                                                        @click="openEdit(item)"
                                                    >
                                                        <Pencil
                                                            class="size-4"
                                                        />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon-sm"
                                                        class="text-destructive"
                                                        :disabled="
                                                            deletingItemId ===
                                                            item.id
                                                        "
                                                        @click="
                                                            destroyItem(item)
                                                        "
                                                    >
                                                        <Trash2
                                                            class="size-4"
                                                        />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-if="props.items.length === 0">
                                        <td
                                            colspan="6"
                                            class="px-4 py-8 text-center text-sm text-muted-foreground"
                                        >
                                            Belum ada item.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </EntityPageSection>
            </section>
        </div>

        <Dialog v-model:open="isOpen">
            <DialogContent
                class="flex max-h-[calc(100dvh-2rem)] w-[calc(100vw-2rem)] !max-w-2xl flex-col overflow-hidden p-4 sm:p-6"
            >
                <DialogHeader class="shrink-0">
                    <DialogTitle>{{ itemDialogTitle }}</DialogTitle>
                </DialogHeader>

                <form
                    class="grid min-h-0 min-w-0 flex-1 gap-4 overflow-x-hidden overflow-y-auto py-2 pr-1 sm:grid-cols-2"
                    @submit.prevent="submitItem"
                >
                    <div class="min-w-0 space-y-2">
                        <Label for="category">Kategori</Label>
                        <Input id="category" v-model="form.category" />
                        <InputError :message="form.errors.category" />
                    </div>

                    <div class="min-w-0 space-y-2">
                        <Label for="sub_category">Sub Kategori</Label>
                        <Input id="sub_category" v-model="form.sub_category" />
                        <InputError :message="form.errors.sub_category" />
                    </div>

                    <div class="min-w-0 space-y-2 sm:col-span-2">
                        <Label for="description">Deskripsi</Label>
                        <Input id="description" v-model="form.description" />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="min-w-0 space-y-2">
                        <Label for="unit">Unit</Label>
                        <Input id="unit" v-model="form.unit" />
                        <InputError :message="form.errors.unit" />
                    </div>

                    <div class="min-w-0 space-y-2">
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

                    <div class="min-w-0 space-y-2">
                        <Label for="unit_price">Harga Satuan</Label>
                        <Input
                            id="unit_price"
                            v-model.number="form.unit_price"
                            type="number"
                            min="0"
                            step="0.01"
                        />
                        <InputError :message="form.errors.unit_price" />
                    </div>

                    <div class="min-w-0 space-y-2">
                        <Label for="total_price">Total Harga</Label>
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
                        <div class="min-w-0 space-y-2">
                            <Label for="spec_brand">Brand</Label>
                            <Input id="spec_brand" v-model="form.spec_brand" />
                            <InputError :message="form.errors.spec_brand" />
                        </div>

                        <div class="min-w-0 space-y-2">
                            <Label for="spec_size">Ukuran</Label>
                            <Input id="spec_size" v-model="form.spec_size" />
                            <InputError :message="form.errors.spec_size" />
                        </div>

                        <div class="min-w-0 space-y-2 sm:col-span-2">
                            <Label for="spec_strength">Kekuatan</Label>
                            <Input
                                id="spec_strength"
                                v-model="form.spec_strength"
                            />
                            <InputError :message="form.errors.spec_strength" />
                        </div>
                    </template>

                    <DialogFooter class="shrink-0 sm:col-span-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeModal"
                            >Batal</Button
                        >
                        <Button type="submit" :disabled="form.processing">
                            {{ editingItemId === null ? 'Simpan' : 'Update' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
