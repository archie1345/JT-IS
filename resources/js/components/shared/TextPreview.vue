<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        text: unknown;
        max?: number;
        placeholder?: string;
    }>(),
    {
        max: 56,
        placeholder: '-',
    },
);

const normalizedText = computed(() => {
    if (props.text === null || props.text === undefined || props.text === '') {
        return props.placeholder;
    }

    return String(props.text);
});

const isTruncated = computed(() => normalizedText.value.length > props.max);
const displayText = computed(() =>
    isTruncated.value
        ? `${normalizedText.value.slice(0, props.max).trimEnd()}...`
        : normalizedText.value,
);
</script>

<template>
    <span
        class="block max-w-full min-w-0 truncate"
        :title="normalizedText"
        :aria-label="isTruncated ? normalizedText : undefined"
    >
        {{ displayText }}
    </span>
</template>
