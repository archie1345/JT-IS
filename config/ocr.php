<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OCR Provider
    |--------------------------------------------------------------------------
    |
    | OCR is an optional assistant for draft extraction. Keep the provider set
    | to "none" for local demo/manual input, or point it at an internal OCR HTTP
    | service when available.
    |
    | Supported providers:
    | - none
    | - laravel_http
    | - google_document_ai (configuration placeholder; manual fallback if absent)
    |
    */

    'provider' => env('OCR_PROVIDER', 'none'),

    'http' => [
        'url' => env('OCR_SERVICE_URL'),
        'timeout' => (int) env('OCR_SERVICE_TIMEOUT', 60),
    ],

    'google_document_ai' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'location' => env('GOOGLE_CLOUD_LOCATION'),
        'processor_id' => env('GOOGLE_DOCUMENT_AI_PROCESSOR_ID'),
        'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    ],

    'max_file_size_kb' => (int) env('OCR_MAX_FILE_SIZE_KB', 20480),

    'allowed_extensions' => [
        'pdf',
        'png',
        'jpg',
        'jpeg',
        'webp',
        'bmp',
        'tif',
        'tiff',
        'txt',
        'csv',
        'doc',
        'docx',
        'xls',
        'xlsx',
    ],
];
