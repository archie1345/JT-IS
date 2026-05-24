export type OcrResponse = {
    engine?: string;
    pages?: {
        angle?: number;
        confidence?: number;
        mode?: string;
        page?: number;
        text?: string;
    }[];
    text?: string;
};

type OcrRequestOptions = {
    endpoint?: string;
};

export const csrfToken = () =>
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.content ?? '';

const cookieValue = (name: string) =>
    document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith(`${name}=`))
        ?.split('=')
        .slice(1)
        .join('=');

const buildOcrRequest = (file: File) => {
    const xsrfToken = cookieValue('XSRF-TOKEN');
    const fallbackToken = csrfToken();
    const formData = new FormData();
    formData.append('file', file);

    if (!xsrfToken && fallbackToken) {
        formData.append('_token', fallbackToken);
    }

    return {
        body: formData,
        headers: {
            Accept: 'application/json',
            ...(xsrfToken
                ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfToken) }
                : { 'X-CSRF-TOKEN': fallbackToken }),
        },
    };
};

const postOcrFile = (file: File, endpoint: string) => {
    const request = buildOcrRequest(file);

    return fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: request.headers,
        body: request.body,
    });
};

export const extractWithLaravelOcr = async (
    file: File,
    options: OcrRequestOptions = {},
): Promise<OcrResponse> => {
    const endpoint = options.endpoint ?? '/projects/documents/ocr';
    let response = await postOcrFile(file, endpoint);

    if (response.status === 419) {
        await fetch('/sanctum/csrf-cookie', {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
            },
        });

        response = await postOcrFile(file, endpoint);
    }

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        const detail =
            typeof payload.detail === 'string' ? payload.detail : null;
        const message =
            typeof payload.message === 'string'
                ? detail
                    ? `${payload.message} ${detail}`
                    : payload.message
                : 'OCR request failed.';

        throw new Error(message);
    }

    return payload as OcrResponse;
};
