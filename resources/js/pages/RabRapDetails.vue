<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ArrowLeft, Minus, Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';

type DetailItem = {
    id: number;
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
        projectName: string;
        totalBudget: number;
        itemCount: number;
        createdAt: string | null;
        updatedAt: string | null;
    };
    items: DetailItem[];
    summary: {
        subtotal: number;
        itemCount: number;
        difference: number;
    };
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: props.recordLabel,
        href: props.kind === 'rab' ? '/rabs' : '/raps',
    },
    {
        title: `${props.recordLabel} #${props.record.id}`,
        href: props.kind === 'rab' ? `/rabs/${props.record.id}` : `/raps/${props.record.id}`,
    },
]);

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatDate = (value: string | null) => value || '-';

const backToList = () => {
    router.get(props.kind === 'rab' ? '/rabs' : '/raps');
};
</script>

<template>
    <Head :title="`${props.title} #${props.record.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-4 rounded-xl p-4">
            <section class="rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="space-y-2">
                        <Button variant="ghost" class="pl-0 text-muted-foreground" @click="backToList">
                            <ArrowLeft class="mr-2 size-4" />
                            Back
                        </Button>
                        <div>
                            <h1 class="text-3xl font-semibold tracking-tight text-foreground">
                                {{ props.record.projectName }}
                            </h1>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ props.recordLabel }} #{{ props.record.id }}
                            </p>
                        </div>
                    </div>

                    <Badge class="bg-blue-500/15 text-blue-600 ring-1 ring-blue-500/25">
                        {{ props.recordLabel }}
                    </Badge>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 shadow-sm">
                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Items</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">{{ props.summary.itemCount }}</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 shadow-sm">
                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Subtotal</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">{{ formatCurrency(props.summary.subtotal) }}</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 shadow-sm">
                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Budget</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">{{ formatCurrency(props.record.totalBudget) }}</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 shadow-sm">
                    <p class="text-xs uppercase tracking-[0.18em] text-muted-foreground">Difference</p>
                    <p class="mt-2 text-2xl font-semibold text-foreground">{{ formatCurrency(props.summary.difference) }}</p>
                </div>
            </section>

            <section class="grid gap-4">
                <div class="overflow-hidden rounded-2xl border border-sidebar-border/70 bg-background shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">
                                Item Details
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Detail list for {{ props.record.projectName }}.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button variant="outline" disabled>
                                Reduce row
                            </Button>
                            <Button disabled>
                                Add row
                            </Button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-muted/40 text-left text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Description</th>
                                    <th class="px-4 py-3 font-medium">Quantity</th>
                                    <th class="px-4 py-3 font-medium">Unit</th>
                                    <th class="px-4 py-3 font-medium">Unit Price</th>
                                    <th class="px-4 py-3 font-medium">Amount</th>
                                    <th class="px-4 py-3 font-medium text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in props.items"
                                    :key="item.id"
                                    class="border-t border-sidebar-border/70 align-top"
                                >
                                    <td class="px-4 py-3">
                                        <Input :model-value="item.description" disabled />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <Button variant="outline" size="icon-sm" disabled>
                                                <Minus class="size-4" />
                                            </Button>
                                            <Input :model-value="item.quantity" type="number" disabled class="w-24" />
                                            <Button variant="outline" size="icon-sm" disabled>
                                                <Plus class="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Input :model-value="item.unit" class="w-24" disabled />
                                    </td>
                                    <td class="px-4 py-3">
                                        <Input :model-value="item.unitPrice" type="number" disabled class="min-w-32" />
                                    </td>
                                    <td class="px-4 py-3 font-medium text-foreground">
                                        {{ formatCurrency(item.totalPrice) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end">
                                            <Button variant="ghost" size="icon-sm" disabled>
                                                <Trash2 class="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
