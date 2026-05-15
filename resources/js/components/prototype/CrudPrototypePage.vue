<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import ProjectDataTable, { type SpreadsheetColumn } from '@/components/ProjectDataTable.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';

type Option = {
    value: number | string;
    label: string;
    hint?: null | string;
};

type FieldType = 'text' | 'number' | 'date' | 'select' | 'textarea';

type Field = {
    name: string;
    label: string;
    type: FieldType;
    placeholder?: string;
    required?: boolean;
    min?: number;
    max?: number;
    step?: string;
    options?: readonly Option[];
};

type Row = Record<string, null | number | string>;
type FormValue = number | string;

const props = withDefaults(defineProps<{
    headTitle: string;
    title: string;
    description: string;
    breadcrumbs: BreadcrumbItem[];
    rows: Row[];
    columns: SpreadsheetColumn[];
    fields: readonly Field[];
    createUrl: string;
    updateUrlBase: string;
    deleteUrlBase: string;
    createLabel?: string;
    note?: string;
}>(), {
    createLabel: 'New Record',
    note: '',
});

const isOpen = ref(false);
const editingId = ref<null | number>(null);
const deletingId = ref<null | number>(null);

const blankState = computed<Record<string, FormValue>>(() =>
    Object.fromEntries(
        props.fields.map((field) => [field.name, '']),
    ),
);

const form = useForm<Record<string, FormValue>>({ ...blankState.value });

const resetForm = () => {
    editingId.value = null;
    const payload = { ...blankState.value };
    form.defaults(payload);
    form.reset();
    form.clearErrors();
    Object.assign(form, payload);
};

const openCreate = () => {
    resetForm();
    isOpen.value = true;
};

const openEdit = (row: Row) => {
    editingId.value = Number(row.id);

    const payload = Object.fromEntries(
        props.fields.map((field) => [field.name, row[field.name] ?? blankState.value[field.name] ?? '']),
    );

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

const submit = () => {
    if (editingId.value === null) {
        form.post(props.createUrl, {
            preserveScroll: true,
            onSuccess: closeModal,
        });

        return;
    }

    form.patch(`${props.updateUrlBase}/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: closeModal,
    });
};

const destroyRecord = (row: Row) => {
    const id = Number(row.id);

    if (!window.confirm('Delete this record?')) {
        return;
    }

    deletingId.value = id;

    router.delete(`${props.deleteUrlBase}/${id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
};

const dialogTitle = computed(() => (editingId.value === null ? `Create ${props.title}` : `Edit ${props.title}`));
</script>

<template>
    <Head :title="props.headTitle" />

    <AppLayout :breadcrumbs="props.breadcrumbs">
        <div class="flex min-h-[calc(100vh-8rem)] flex-1 flex-col gap-4 rounded-xl p-4">
            <ProjectDataTable
                :rows="props.rows"
                :columns="props.columns"
                :title="props.title"
                :description="props.description"
                :note="props.note"
                show-create-button
                :create-label="props.createLabel"
                @create="openCreate"
            >
                <template #actions="{ row }">
                    <div class="flex justify-end gap-1">
                        <Button variant="ghost" size="icon-sm" @click="openEdit(row as Row)">
                            <Pencil class="size-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-destructive"
                            :disabled="deletingId === Number((row as Row).id)"
                            @click="destroyRecord(row as Row)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </template>

                <template
                    v-for="(_, slotName) in $slots"
                    :key="String(slotName)"
                    #[slotName]="slotProps"
                >
                    <slot :name="slotName" v-bind="slotProps" />
                </template>
            </ProjectDataTable>
        </div>

        <Dialog v-model:open="isOpen">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ dialogTitle }}</DialogTitle>
                </DialogHeader>

                <form class="grid gap-4 py-2 md:grid-cols-2" @submit.prevent="submit">
                    <div
                        v-for="field in props.fields"
                        :key="field.name"
                        class="space-y-2"
                        :class="field.type === 'textarea' ? 'md:col-span-2' : ''"
                    >
                        <Label :for="field.name">{{ field.label }}</Label>

                        <select
                            v-if="field.type === 'select'"
                            :id="field.name"
                            v-model="form[field.name]"
                            :required="field.required"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                        >
                            <option value="">Select one</option>
                            <option
                                v-for="option in field.options ?? []"
                                :key="String(option.value)"
                                :value="option.value"
                            >
                                {{ option.label }}{{ option.hint ? ` - ${option.hint}` : '' }}
                            </option>
                        </select>

                        <textarea
                            v-else-if="field.type === 'textarea'"
                            :id="field.name"
                            v-model="form[field.name]"
                            :placeholder="field.placeholder"
                            :required="field.required"
                            class="flex min-h-28 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                        />

                        <Input
                            v-else
                            :id="field.name"
                            v-model="form[field.name]"
                            :type="field.type"
                            :placeholder="field.placeholder"
                            :min="field.min"
                            :max="field.max"
                            :step="field.step"
                            :required="field.required"
                        />

                        <InputError :message="form.errors[field.name]" />
                    </div>

                    <DialogFooter class="md:col-span-2">
                        <Button type="button" variant="outline" @click="closeModal">Cancel</Button>
                        <Button type="submit" :disabled="form.processing">
                            {{ editingId === null ? 'Save' : 'Update' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
