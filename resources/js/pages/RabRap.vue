<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Minus, Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';

type RabItem = {
    id: number;
    description: string;
    volume: number;
    unit: string;
    unitPrice: number;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'RAB & RAP',
        href: '/rab-rap',
    },
];

const createItem = (id: number, overrides: Partial<RabItem> = {}): RabItem => ({
    id,
    description: overrides.description ?? `Work Item ${id}`,
    volume: overrides.volume ?? 1,
    unit: overrides.unit ?? 'm2',
    unitPrice: overrides.unitPrice ?? 150000,
});

const items = ref<RabItem[]>([
    createItem(1, { description: 'Site preparation', unit: 'lot', unitPrice: 2500000 }),
    createItem(2, { description: 'Concrete casting', unit: 'm3', unitPrice: 925000 }),
    createItem(3, { description: 'Rebar installation', unit: 'kg', unitPrice: 18500 }),
]);

const rowTarget = ref(items.value.length);
const nextItemId = ref(items.value.length + 1);
const projectName = ref('Dummy RAB Proyek Gedung');
const taxRate = ref(11);

const clampRowTarget = (value: number) => {
    if (!Number.isFinite(value) || value < 1) {
        return 1;
    }

    return Math.min(25, Math.floor(value));
};

const syncRowsToTarget = (target: number) => {
    const safeTarget = clampRowTarget(target);

    while (items.value.length < safeTarget) {
        items.value.push(createItem(nextItemId.value));
        nextItemId.value += 1;
    }

    while (items.value.length > safeTarget) {
        items.value.pop();
    }

    rowTarget.value = safeTarget;
};

watch(rowTarget, (target) => {
    syncRowsToTarget(target);
});

const addRow = () => {
    rowTarget.value += 1;
};

const removeLastRow = () => {
    if (items.value.length === 1) {
        return;
    }

    rowTarget.value -= 1;
};

const removeRow = (index: number) => {
    if (items.value.length === 1) {
        return;
    }

    items.value.splice(index, 1);
    rowTarget.value = items.value.length;
};

const updateVolume = (index: number, delta: number) => {
    const item = items.value[index];

    if (!item) {
        return;
    }

    item.volume = Math.max(0, Number((item.volume + delta).toFixed(2)));
};

const subtotal = computed(() =>
    items.value.reduce((sum, item) => sum + item.volume * item.unitPrice, 0),
);

const taxAmount = computed(() => subtotal.value * (taxRate.value / 100));
const grandTotal = computed(() => subtotal.value + taxAmount.value);

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);
</script>

<template>
    <Head title="RAB & RAP" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <section class="rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted-foreground">
                            Dummy RAB page
                        </p>
                        <h1 class="text-2xl font-semibold text-foreground">
                            RAB & RAP Estimator
                        </h1>
                        <p class="max-w-2xl text-sm text-muted-foreground">
                            This prototype grows or shrinks the table from user input, while each row still lets you tweak quantity and pricing manually.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:min-w-[26rem]">
                        <label class="space-y-2 text-sm">
                            <span class="font-medium text-foreground">Project name</span>
                            <Input v-model="projectName" />
                        </label>
                        <label class="space-y-2 text-sm">
                            <span class="font-medium text-foreground">Target row count</span>
                            <Input v-model.number="rowTarget" type="number" min="1" max="25" />
                        </label>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="overflow-hidden rounded-2xl border border-sidebar-border/70 bg-background shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">RAB Line Items</h2>
                            <p class="text-sm text-muted-foreground">
                                Add, subtract, or edit values to simulate a budgeting worksheet.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button variant="outline" @click="removeLastRow">
                                Reduce row
                            </Button>
                            <Button @click="addRow">
                                Add row
                            </Button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-muted/40 text-left text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Description</th>
                                    <th class="px-4 py-3 font-medium">Volume</th>
                                    <th class="px-4 py-3 font-medium">Unit</th>
                                    <th class="px-4 py-3 font-medium">Unit Price</th>
                                    <th class="px-4 py-3 font-medium">Amount</th>
                                    <th class="px-4 py-3 font-medium text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, index) in items"
                                    :key="item.id"
                                    class="border-t border-sidebar-border/70 align-top"
                                >
                                    <td class="px-4 py-3">
                                        <Input v-model="item.description" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <Button
                                                variant="outline"
                                                size="icon-sm"
                                                @click="updateVolume(index, -1)"
                                            >
                                                <Minus class="size-4" />
                                            </Button>
                                            <Input v-model.number="item.volume" type="number" step="0.01" min="0" class="w-24" />
                                            <Button
                                                variant="outline"
                                                size="icon-sm"
                                                @click="updateVolume(index, 1)"
                                            >
                                                <Plus class="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Input v-model="item.unit" class="w-24" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <Input v-model.number="item.unitPrice" type="number" min="0" step="1000" class="min-w-32" />
                                    </td>
                                    <td class="px-4 py-3 font-medium text-foreground">
                                        {{ formatCurrency(item.volume * item.unitPrice) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end">
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                :disabled="items.length === 1"
                                                @click="removeRow(index)"
                                            >
                                                <Trash2 class="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <aside class="space-y-4 rounded-2xl border border-sidebar-border/70 bg-background/95 p-5 shadow-sm">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-muted-foreground">
                            Summary
                        </p>
                        <h2 class="mt-1 text-lg font-semibold text-foreground">
                            {{ projectName }}
                        </h2>
                    </div>

                    <label class="block space-y-2 text-sm">
                        <span class="font-medium text-foreground">Tax / PPN (%)</span>
                        <Input v-model.number="taxRate" type="number" min="0" max="100" />
                    </label>

                    <div class="space-y-3 rounded-xl border border-sidebar-border/70 bg-muted/20 p-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">Rows</span>
                            <span class="font-medium text-foreground">{{ items.length }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span class="font-medium text-foreground">{{ formatCurrency(subtotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">Tax</span>
                            <span class="font-medium text-foreground">{{ formatCurrency(taxAmount) }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-sidebar-border/70 pt-3 text-base">
                            <span class="font-semibold text-foreground">Grand Total</span>
                            <span class="font-semibold text-foreground">{{ formatCurrency(grandTotal) }}</span>
                        </div>
                    </div>

                    <p class="text-sm leading-6 text-muted-foreground">
                        This is a front-end dummy page only, so the values are not saved yet. It is useful for testing interaction and table flow first.
                    </p>
                </aside>
            </section>
        </div>
    </AppLayout>
</template>
