export type OcrResponse = {
    engine?: string;
    pages?: {
        angle?: number;
        confidence?: number;
        mode?: string;
        page?: number;
        text?: string;
    }[];
    message?: string;
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

const csrfRequestHeaders = (headers?: HeadersInit) => {
    const requestHeaders = new Headers(headers);
    const xsrfToken = cookieValue('XSRF-TOKEN');
    const fallbackToken = csrfToken();

    if (xsrfToken) {
        requestHeaders.set('X-XSRF-TOKEN', decodeURIComponent(xsrfToken));
    } else if (fallbackToken) {
        requestHeaders.set('X-CSRF-TOKEN', fallbackToken);
    }

    return requestHeaders;
};

const refreshCsrfCookie = () =>
    fetch('/sanctum/csrf-cookie', {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
        },
    });

export const csrfFetch = async (
    input: RequestInfo | URL,
    init: RequestInit = {},
) => {
    const buildRequest = (): RequestInit => ({
        ...init,
        credentials: init.credentials ?? 'same-origin',
        headers: csrfRequestHeaders(init.headers),
    });

    let response = await fetch(input, buildRequest());

    if (response.status === 419) {
        await refreshCsrfCookie();
        response = await fetch(input, buildRequest());
    }

    return response;
};

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
        headers: csrfRequestHeaders({
            Accept: 'application/json',
        }),
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
        await refreshCsrfCookie();
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
