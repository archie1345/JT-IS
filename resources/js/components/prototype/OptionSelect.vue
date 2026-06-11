<script setup lang="ts">
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import TextPreview from '@/components/shared/TextPreview.vue';

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
        placeholder: 'Pilih salah satu',
        emptyLabel: 'Pilih salah satu',
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

const selectedTitle = computed(() => {
    if (props.allowEmpty && selectValue.value === EMPTY_SELECT_VALUE) {
        return props.emptyLabel;
    }

    const option = props.options.find(
        (item) => String(item.value) === selectValue.value,
    );

    if (!option) {
        return props.placeholder;
    }

    return option.hint ? `${option.label} - ${option.hint}` : option.label;
});
</script>

<template>
    <Select v-model="selectValue">
        <SelectTrigger
            :id="props.triggerId"
            class="w-full min-w-0"
            :title="selectedTitle"
        >
            <SelectValue :placeholder="props.placeholder" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem v-if="props.allowEmpty" :value="EMPTY_SELECT_VALUE">
                <TextPreview :text="props.emptyLabel" :max="42" />
            </SelectItem>
            <SelectItem
                v-for="option in props.options"
                :key="String(option.value)"
                :value="String(option.value)"
                :title="
                    option.hint
                        ? `${option.label} - ${option.hint}`
                        : option.label
                "
            >
                <span class="max-w-72 min-w-0 overflow-hidden">
                    <TextPreview :text="option.label" :max="42" />
                    <TextPreview
                        v-if="option.hint"
                        :text="option.hint"
                        :max="48"
                        class="text-xs text-muted-foreground"
                    />
                </span>
            </SelectItem>
        </SelectContent>
    </Select>
</template>
