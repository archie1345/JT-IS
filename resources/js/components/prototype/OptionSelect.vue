<script setup lang="ts">
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Option = {
    value: number | string;
    label: string;
    hint?: null | string;
};

const EMPTY_SELECT_VALUE = '__empty__';

const props = withDefaults(
    defineProps<{
        options: readonly Option[];
        placeholder?: string;
        emptyLabel?: string;
        allowEmpty?: boolean;
        triggerId?: string;
    }>(),
    {
        placeholder: 'Select one',
        emptyLabel: 'Select one',
        allowEmpty: false,
        triggerId: undefined,
    },
);

const model = defineModel<string>({ required: true });

const selectValue = computed({
    get: () =>
        props.allowEmpty && !model.value ? EMPTY_SELECT_VALUE : model.value,
    set: (value: string) => {
        model.value =
            props.allowEmpty && value === EMPTY_SELECT_VALUE ? '' : value;
    },
});
</script>

<template>
    <Select v-model="selectValue">
        <SelectTrigger :id="props.triggerId" class="w-full">
            <SelectValue :placeholder="props.placeholder" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem v-if="props.allowEmpty" :value="EMPTY_SELECT_VALUE">
                {{ props.emptyLabel }}
            </SelectItem>
            <SelectItem
                v-for="option in props.options"
                :key="String(option.value)"
                :value="String(option.value)"
            >
                <span class="min-w-0">
                    <span class="block truncate">{{ option.label }}</span>
                    <span
                        v-if="option.hint"
                        class="block truncate text-xs text-muted-foreground"
                    >
                        {{ option.hint }}
                    </span>
                </span>
            </SelectItem>
        </SelectContent>
    </Select>
</template>
