<script setup lang="ts">
import { ref } from 'vue';
import { Upload, LoaderCircle, CheckCircle } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { extractWithLaravelOcr } from '@/lib/ocr';

const emit = defineEmits(['data-extracted']);
const props = withDefaults(
    defineProps<{
        endpoint?: string;
    }>(),
    {
        endpoint: '/projects/documents/ocr',
    },
);
const isReading = ref(false);
const fileName = ref<string | null>(null);

const handleFile = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    fileName.value = file.name;
    isReading.value = true;

    try {
        const result = await extractWithLaravelOcr(file, {
            endpoint: props.endpoint,
        });

        if (result.text) {
            emit('data-extracted', result.text);
            toast.success("Dokumen berhasil diproses!");
        } else {
            toast.error("Tidak ada teks yang bisa dibaca dari dokumen.");
        }
    } catch {
        toast.error("Gagal memproses dokumen.");
    } finally {
        isReading.value = false;
    }
};
</script>

<template>
    <div class="space-y-4 rounded-xl border border-dashed border-sidebar-border/70 bg-muted/20 p-4">
        <label class="flex cursor-pointer flex-col items-center justify-center gap-2 py-4">
            <Upload class="size-6 text-primary" />
            <span class="text-sm font-medium">Upload File (RAB/SPH)</span>
            <input type="file" class="hidden" @change="handleFile" accept=".pdf,.png,.jpg" />
        </label>

        <div v-if="isReading" class="flex items-center justify-center gap-2 text-sm text-muted-foreground">
            <LoaderCircle class="size-4 animate-spin" />
            <span>Membaca dokumen...</span>
        </div>

        <div v-if="fileName && !isReading" class="flex items-center justify-center gap-2 text-sm text-emerald-600">
            <CheckCircle class="size-4" />
            <span>{{ fileName }} siap diproses</span>
        </div>
    </div>
</template>
