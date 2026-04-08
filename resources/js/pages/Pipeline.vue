<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Trash2, ExternalLink } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';

type PaymentStatus = 'pending' | 'partial' | 'paid' | 'overdue';
type ProjectStatus = 'not_started' | 'planning' | 'ongoing' | 'completed';

type PipelineItem = {
    id: number;
    projectName: string;
    client: string;
    estPrice: number;
    deadline: string;
    paymentStatus: PaymentStatus;
    projectStatus: ProjectStatus;
};

const paymentStatusOptions: { label: string; value: PaymentStatus }[] = [
    { label: 'Pending', value: 'pending' },
    { label: 'Partial', value: 'partial' },
    { label: 'Paid', value: 'paid' },
    { label: 'Overdue', value: 'overdue' },
];

const projectStatusOptions: { label: string; value: ProjectStatus }[] = [
    { label: 'Not Started', value: 'not_started' },
    { label: 'Planning', value: 'planning' },
    { label: 'Ongoing', value: 'ongoing' },
    { label: 'Completed', value: 'completed' },
];

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pipelines',
        href: '/pipeline',
    },
];

const createItem = (id: number, overrides: Partial<PipelineItem> = {}): PipelineItem => ({
    id,
    projectName: overrides.projectName ?? `Work Item ${id}`,
    client: overrides.client ?? '',
    estPrice: overrides.estPrice ?? 0,
    deadline: overrides.deadline ?? '',
    paymentStatus: overrides.paymentStatus ?? 'pending',
    projectStatus: overrides.projectStatus ?? 'not_started',
});

const items = ref<PipelineItem[]>([
    createItem(1, {
        projectName: 'Warehouse Expansion',
        client: 'PT Nusantara',
        estPrice: 425000000,
        deadline: '2026-05-20',
        paymentStatus: 'pending',
        projectStatus: 'planning',
    }),
    createItem(2, {
        projectName: 'Office Renovation',
        client: 'CV Sejahtera',
        estPrice: 180000000,
        deadline: '2026-06-15',
        paymentStatus: 'partial',
        projectStatus: 'ongoing',
    }),
]);

const rowTarget = ref(items.value.length);
const nextItemId = ref(items.value.length + 1);

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
const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const openProject = (item: PipelineItem) => {
    // Implement project opening logic here
    console.log('Opening project:', item);
};

</script>

<template>
    <Head title="RAB & RAP" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <section class="rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm">
                <div class="overflow-hidden rounded-2xl border border-sidebar-border/70 bg-background shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">Pipelines</h2>
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
                                    <th class="px-4 py-3 font-medium">Id</th>
                                    <th class="px-4 py-3 font-medium">Project Name</th>
                                    <th class="px-4 py-3 font-medium">Client</th>
                                    <th class="px-4 py-3 font-medium">Est. Value</th>
                                    <th class="px-4 py-3 font-medium">Deadline</th>
                                    <th class="px-4 py-3 font-medium">Payment Status</th>
                                    <th class="px-4 py-3 font-medium">Project Status</th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, index) in items"
                                    :key="item.id"
                                    class="border-t border-sidebar-border/70 align-top"
                                >
                                    <td class="px-4 py-3">
                                        {{ item.id }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ item.projectName }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ item.client }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ formatCurrency(item.estPrice) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ item.deadline }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <Select
                                            v-model="item.paymentStatus"
                                        >
                                            <SelectTrigger class="w-36">
                                                <SelectValue placeholder="Select payment status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="option in paymentStatusOptions"
                                                    :key="option.value"
                                                    :value="option.value"
                                                >
                                                    {{ option.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Select
                                            v-model="item.projectStatus"
                                        >
                                            <SelectTrigger class="w-36">
                                                <SelectValue placeholder="Select project status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="option in projectStatusOptions"
                                                    :key="option.value"
                                                    :value="option.value"
                                                >
                                                    {{ option.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end">
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                :disabled="items.length === 1"
                                                @click="openProject(item)"
                                            >
                                                <external-link class="size-4" />
                                            </Button>
                                        </div>
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
            </section>
        </div>
    </AppLayout>
</template>
