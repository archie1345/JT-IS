<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ExternalLink } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

type ClientItem = {
    id: number;
    name: string | null;
    contact: string | null;
    projectCount: number;
    totalProjectValue: number;
};

const props = defineProps<{
    clients: ClientItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Clients',
        href: '/client',
    },
];

const formatCurrency = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const openProject = (item: ClientItem) => {
    router.get('/projects', {
        client: item.id,
    });
};
</script>

<template>
    <Head title="Clients" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <section class="rounded-2xl border border-sidebar-border/70 bg-background/80 p-5 shadow-sm">
                <div class="overflow-hidden rounded-2xl border border-sidebar-border/70 bg-background shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/70 px-5 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-foreground">Clients</h2>
                            <p class="text-sm text-muted-foreground">
                                Data below is loaded from the database and summarizes each client portfolio.
                            </p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-muted/40 text-left text-muted-foreground">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Id</th>
                                    <th class="px-4 py-3 font-medium">Client Name</th>
                                    <th class="px-4 py-3 font-medium">Contact</th>
                                    <th class="px-4 py-3 font-medium">Projects</th>
                                    <th class="px-4 py-3 font-medium">Total Contract Value</th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in props.clients"
                                    :key="item.id"
                                    class="border-t border-sidebar-border/70 align-top"
                                >
                                    <td class="px-4 py-3">
                                        {{ item.id }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ item.name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ item.contact ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ item.projectCount }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ formatCurrency(item.totalProjectValue) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex justify-end">
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                @click="openProject(item)"
                                            >
                                                <ExternalLink class="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="props.clients.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                        No client data found yet.
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
