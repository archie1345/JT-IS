<script setup lang="ts">
import { ref } from 'vue';
import { Upload, LoaderCircle, CheckCircle } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { useDocumentOcr } from '@/composables/useDocumentOcr';

const emit = defineEmits(['data-extracted']);
const props = withDefaults(
    defineProps<{
        endpoint?: string;
        label?: string;
    }>(),
    {
        endpoint: '/projects/documents/ocr',
        label: 'Upload Dokumen',
    },
);

const { error, extractFile, isReading, status, text } = useDocumentOcr({
    emptyTextMessage: 'Tidak ada teks yang bisa dibaca dari dokumen.',
    failedStatus: 'Gagal memproses dokumen.',
    initialStatus: 'Membaca dokumen...',
    successStatus: 'Dokumen berhasil diproses',
});
const fileName = ref<null | string>(null);

const handleFile = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    fileName.value = file.name;
    try {
        const result = await extractFile(file, {
            endpoint: props.endpoint,
        });

        if (result.text) {
            emit('data-extracted', result.text);
            toast.success('Dokumen berhasil diproses!');
        } else {
            toast.error(
                error.value ?? 'Tidak ada teks yang bisa dibaca dari dokumen.',
            );
        }
    } catch {
        toast.error(error.value ?? 'Gagal memproses dokumen.');
    }
};
</script>

<template>
    <div
        class="space-y-4 rounded-xl border border-dashed border-sidebar-border/70 bg-muted/20 p-4"
    >
        <label
            class="flex cursor-pointer flex-col items-center justify-center gap-2 py-4"
        >
            <Upload class="size-6 text-primary" />
            <span class="text-sm font-medium">{{ props.label }}</span>
            <input
                type="file"
                class="hidden"
                @change="handleFile"
                accept=".pdf,.png,.jpg"
            />
        </label>

        <div
            v-if="isReading"
            class="flex items-center justify-center gap-2 text-sm text-muted-foreground"
        >
            <LoaderCircle class="size-4 animate-spin" />
            <span>{{ status ?? 'Membaca dokumen...' }}</span>
        </div>

        <div
            v-if="fileName && !isReading && text"
            class="flex items-center justify-center gap-2 text-sm text-emerald-600"
        >
            <CheckCircle class="size-4" />
            <span>{{ fileName }} siap diproses</span>
        </div>

        <p v-if="error && !text" class="text-center text-sm text-destructive">
            {{ error }}
        </p>
    </div>
</template>
