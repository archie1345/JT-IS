<?php

namespace App\Services\Ocr;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OcrService
{
    public function __construct(
        protected OcrResultNormalizer $normalizer,
        protected BudgetLineItemExtractor $budgetLineItemExtractor,
        protected ProgressDocumentExtractor $progressDocumentExtractor,
        protected InvoiceDocumentExtractor $invoiceDocumentExtractor,
    ) {
    }

    public function configured(): bool
    {
        $provider = $this->provider();

        if ($provider === 'none') {
            return false;
        }

        if ($provider === 'laravel_http') {
            return filled(config('ocr.http.url'));
        }

        if ($provider === 'google_document_ai') {
            return collect(config('ocr.google_document_ai'))
                ->except('credentials')
                ->every(fn ($value): bool => filled($value));
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function extract(UploadedFile $file): array
    {
        if ($this->isPlainText($file)) {
            return $this->withDraftExtractors([
                'engine' => 'laravel-text',
                'text' => file_get_contents($file->getRealPath()) ?: '',
                'pages' => [],
                'confidence' => null,
                'message' => null,
            ]);
        }

        if (! $this->configured()) {
            throw new OcrNotConfiguredException(
                'OCR belum dikonfigurasi. Silakan input manual atau hubungi admin.'
            );
        }

        return match ($this->provider()) {
            'laravel_http' => $this->extractWithHttpProvider($file),
            'google_document_ai' => throw new OcrProviderException(
                'Google Document AI belum tersedia di build demo ini. Silakan input manual.'
            ),
            default => throw new OcrNotConfiguredException(
                'OCR belum dikonfigurasi. Silakan input manual atau hubungi admin.'
            ),
        };
    }

    protected function provider(): string
    {
        return strtolower((string) config('ocr.provider', 'none'));
    }

    protected function isPlainText(UploadedFile $file): bool
    {
        return in_array(strtolower($file->getClientOriginalExtension()), ['txt', 'csv'], true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractWithHttpProvider(UploadedFile $file): array
    {
        $ocrUrl = rtrim((string) config('ocr.http.url'), '/').'/ocr';

        try {
            $response = Http::connectTimeout(10)
                ->timeout((int) config('ocr.http.timeout', 60))
                ->attach(
                    'file',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName(),
                )
                ->post($ocrUrl);
        } catch (ConnectionException $exception) {
            Log::warning('OCR provider timeout', [
                'provider' => $this->provider(),
                'file' => $file->getClientOriginalName(),
                'message' => $exception->getMessage(),
            ]);

            throw new OcrProviderException(
                'OCR belum merespons. Silakan coba lagi atau input manual.',
                504,
            );
        }

        if ($response->failed()) {
            Log::warning('OCR provider failed', [
                'provider' => $this->provider(),
                'status' => $response->status(),
                'file' => $file->getClientOriginalName(),
            ]);

            throw new OcrProviderException(
                'OCR gagal memproses dokumen. Silakan input manual.',
                $response->status() >= 500 ? 503 : $response->status(),
            );
        }

        return $this->withDraftExtractors(
            $this->normalizer->normalize($response->json() ?? [], 'laravel-http')
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function withDraftExtractors(array $payload): array
    {
        $text = (string) ($payload['text'] ?? '');

        return [
            ...$payload,
            'draft' => [
                'budget_items' => $this->budgetLineItemExtractor->extract($text),
                'progress' => $this->progressDocumentExtractor->extract($text),
                'invoice' => $this->invoiceDocumentExtractor->extract($text),
            ],
        ];
    }
}
