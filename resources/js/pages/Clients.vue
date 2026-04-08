<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Trash2, ExternalLink } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';


type ClientItem = {
    id: number;
    ClientName: string;
    estPrice: number;
    email?: string;
    phoneNumber?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clients',
        href: '/clients',
    },
];

const createItem = (id: number, overrides: Partial<ClientItem> = {}): ClientItem => ({
    id,
    ClientName: overrides.ClientName ?? `Client ${id}`,
    estPrice: overrides.estPrice ?? 0,
    email: overrides.email ?? '',
    phoneNumber: overrides.phoneNumber ?? '',
});

const items = ref<ClientItem[]>([
    createItem(1, {
        ClientName: 'PT Nusantara',
        estPrice: 425000000,
        email: 'john.doe@nusantara.com',
        phoneNumber: '+62 812 3456 7890',
    }),
    createItem(2, {
        ClientName: 'CV Sejahtera',
        estPrice: 180000000,
        email: 'jane.smith@sejahtera.com',
        phoneNumber: '+62 812 3456 7891',
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

const openProject = (item: ClientItem) => {
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
                                    <th class="px-4 py-3 font-medium">Client Name</th>
                                    <th class="px-4 py-3 font-medium">Email</th>
                                    <th class="px-4 py-3 font-medium">Phone Number</th>
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
                                        {{ item.ClientName }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ item.email }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ item.phoneNumber }}
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
