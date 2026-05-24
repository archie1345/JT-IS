<script setup lang="ts">
import { ref } from 'vue';
import { Upload, LoaderCircle, CheckCircle, FileText } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { toast } from 'vue-sonner';

const emit = defineEmits(['data-extracted']);
const isReading = ref(false);
const fileName = ref<string | null>(null);

const handleFile = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;

    fileName.value = file.name;
    isReading.value = true;

    try {
        const formData = new FormData();
        formData.append('file', file);
        
        const response = await fetch('/ai-document-extraction/ocr', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });

        const result = await response.json();
        if (result.text) {
            emit('data-extracted', result.text);
            toast.success("Dokumen berhasil diproses!");
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