<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import OptionSelect from '@/components/prototype/OptionSelect.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Option = {
    value: number | string;
    label: string;
    hint?: null | string;
};

type Field = {
    name: string;
    label: string;
    type: 'date' | 'number' | 'select' | 'text' | 'textarea';
    placeholder?: string;
    required?: boolean;
    min?: number;
    max?: number;
    step?: string;
    options?: readonly Option[];
};

const props = defineProps<{
    field: Field;
    error?: string;
}>();

const model = defineModel<number | string>({ required: true });

const selectValue = computed({
    get: () =>
        model.value === '' || model.value === null || model.value === undefined
            ? ''
            : String(model.value),
    set: (value: string) => {
        model.value = value;
    },
});
</script>

<template>
    <div
        class="space-y-2"
        :class="props.field.type === 'textarea' ? 'sm:col-span-2' : ''"
    >
        <Label :for="props.field.name">{{ props.field.label }}</Label>

        <OptionSelect
            v-if="props.field.type === 'select'"
            v-model="selectValue"
            :options="props.field.options ?? []"
            :trigger-id="props.field.name"
            placeholder="Select one"
            empty-label="Select one"
            allow-empty
        />

        <textarea
            v-else-if="props.field.type === 'textarea'"
            :id="props.field.name"
            v-model="model"
            :placeholder="props.field.placeholder"
            :required="props.field.required"
            class="flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
        />

        <Input
            v-else
            :id="props.field.name"
            v-model="model"
            :type="props.field.type"
            :placeholder="props.field.placeholder"
            :min="props.field.min"
            :max="props.field.max"
            :step="props.field.step"
            :required="props.field.required"
        />

        <InputError :message="props.error" />
    </div>
</template>
