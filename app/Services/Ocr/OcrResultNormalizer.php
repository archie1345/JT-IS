<?php

namespace App\Services\Ocr;

class OcrResultNormalizer
{

    public function normalize(array $payload, string $fallbackEngine): array
    {
        $text = $payload['text'] ?? $payload['ocr_text'] ?? $payload['content'] ?? '';

        return [
            'engine' => (string) ($payload['engine'] ?? $fallbackEngine),
            'text' => is_string($text) ? $text : '',
            'pages' => is_array($payload['pages'] ?? null) ? $payload['pages'] : [],
            'confidence' => is_numeric($payload['confidence'] ?? null)
                ? (float) $payload['confidence']
                : null,
            'message' => is_string($payload['message'] ?? null) ? $payload['message'] : null,
        ];
    }
}
