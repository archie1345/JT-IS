import { computed, ref } from 'vue';
import { extractWithLaravelOcr, type OcrResponse } from '@/lib/ocr';

type DocumentOcrOptions = {
    endpoint?: string;
    emptyTextMessage?: string;
    failedStatus?: string;
    initialStatus?: string;
    successStatus?: string;
};

export const useDocumentOcr = (options: DocumentOcrOptions = {}) => {
    const isReading = ref(false);
    const progress = ref(0);
    const status = ref<null | string>(null);
    const error = ref<null | string>(null);
    const text = ref('');
    const engine = ref<null | string>(null);

    const hasState = computed(() =>
        Boolean(status.value || error.value || text.value),
    );
    const preview = computed(() => text.value.trim().slice(0, 600));

    const reset = () => {
        isReading.value = false;
        progress.value = 0;
        status.value = null;
        error.value = null;
        text.value = '';
        engine.value = null;
    };

    const extractFile = async (
        file: File,
        overrides: DocumentOcrOptions = {},
    ): Promise<OcrResponse> => {
        const config = { ...options, ...overrides };

        error.value = null;
        status.value = config.initialStatus ?? 'Uploading to OCR service';
        progress.value = 10;
        isReading.value = true;
        text.value = '';
        engine.value = null;

        try {
            const payload = await extractWithLaravelOcr(file, {
                endpoint: config.endpoint,
            });

            text.value = typeof payload.text === 'string' ? payload.text : '';
            engine.value =
                typeof payload.engine === 'string' ? payload.engine : 'ocr';
            status.value = engine.value
                ? `${config.successStatus ?? 'Extraction complete'} via ${engine.value}`
                : (config.successStatus ?? 'Extraction complete');
            progress.value = 100;

            if (!text.value.trim()) {
                error.value =
                    config.emptyTextMessage ??
                    'No readable text was returned by OCR.';
            }

            return payload;
        } catch (caught) {
            error.value =
                caught instanceof Error
                    ? caught.message
                    : 'Unable to OCR this document.';
            status.value = config.failedStatus ?? 'OCR failed';
            progress.value = 0;
            throw caught;
        } finally {
            isReading.value = false;
        }
    };

    return {
        engine,
        error,
        extractFile,
        hasState,
        isReading,
        preview,
        progress,
        reset,
        status,
        text,
    };
};
