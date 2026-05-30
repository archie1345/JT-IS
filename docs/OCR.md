# OCR Helpers

OCR in JT-IS is optional. It is only an assistant for drafting data from uploaded business documents. Users must review and apply extracted data manually.

## Available Locations

- Project detail: upload project documents and run OCR before saving extracted text.
- RAB/RAP detail pages: review extracted budget rows before applying them as draft items.
- Progress / BAMC: review suggested progress percentage before creating or updating a progress report.
- Invoices and cost realization: review suggested amount/document fields before applying.

OCR is not used on dashboard, login, admin, or permission pages.

## Configuration

Default behavior is manual input only:

```env
OCR_PROVIDER=none
```

For an internal HTTP OCR service:

```env
OCR_PROVIDER=laravel_http
OCR_SERVICE_URL=http://127.0.0.1:8001
OCR_SERVICE_TIMEOUT=60
OCR_MAX_FILE_SIZE_KB=20480
```

Google Document AI env placeholders are documented for future integration:

```env
OCR_PROVIDER=google_document_ai
GOOGLE_CLOUD_PROJECT_ID=
GOOGLE_CLOUD_LOCATION=
GOOGLE_DOCUMENT_AI_PROCESSOR_ID=
GOOGLE_APPLICATION_CREDENTIALS=
```

Google Document AI is intentionally not required for the MVP demo.

## Safety Rules

- Missing OCR configuration returns a friendly message: `OCR belum dikonfigurasi. Silakan input manual atau hubungi admin.`
- When OCR is not configured, the upload panel skips automatic OCR and shows a manual-input notice instead.
- OCR never silently overwrites financial or project data.
- RAB/RAP OCR rows are applied only after the user opens the review table and clicks apply.
- Progress OCR does not auto-approve internal/client approval flags.
- Invoice OCR apply still respects approved-progress billing limits.
- File upload is limited to common document/image types and a configurable size limit.

## Limitations

OCR confidence and document classification are best-effort. Users should treat every result as `Draft hasil OCR` and review values against the original document.
