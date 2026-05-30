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

const isPercentField = computed(
    () => props.field.type === 'number' && props.field.name.includes('percent'),
);

const selectValue = computed({
    get: () =>
        model.value === '' || model.value === null || model.value === undefined
            ? ''
            : String(model.value),
    set: (value: string) => {
        model.value = value;
    },
});

const sanitizePercentInput = (value: string) => {
    const cleaned = value
        .replace(',', '.')
        .replace(/[^\d.]/g, '')
        .replace(/(\..*)\./g, '$1');
    const [whole = '', decimal] = cleaned.split('.');
    const cappedWhole =
        whole === ''
            ? ''
            : String(Math.min(Number(whole), props.field.max ?? 100));
    const cappedDecimal =
        decimal === undefined ? '' : `.${decimal.slice(0, 2)}`;
    const nextValue = `${cappedWhole}${cappedDecimal}`;
    const maxValue = props.field.max ?? 100;

    return nextValue !== '' && Number(nextValue) > maxValue
        ? String(maxValue)
        : nextValue;
};

const handleInput = (event: Event) => {
    if (!isPercentField.value || !(event.target instanceof HTMLInputElement)) {
        return;
    }

    const sanitizedValue = sanitizePercentInput(event.target.value);
    event.target.value = sanitizedValue;
    model.value = sanitizedValue;
};
</script>

<template>
    <div
        class="min-w-0 space-y-2"
        :class="props.field.type === 'textarea' ? 'sm:col-span-2' : ''"
    >
        <Label :for="props.field.name" class="break-words">
            {{ props.field.label }}
        </Label>

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
            class="flex min-h-32 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
        />

        <Input
            v-else
            :id="props.field.name"
            v-model="model"
            :type="isPercentField ? 'text' : props.field.type"
            :inputmode="isPercentField ? 'decimal' : undefined"
            :pattern="isPercentField ? '^\\d{0,3}(\\.\\d{0,2})?$' : undefined"
            :placeholder="props.field.placeholder"
            :min="props.field.min"
            :max="props.field.max"
            :step="props.field.step"
            :required="props.field.required"
            class="w-full min-w-0 text-sm"
            @input="handleInput"
        />

        <InputError :message="props.error" />
    </div>
</template>
