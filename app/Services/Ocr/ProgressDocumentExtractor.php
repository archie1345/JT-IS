<?php

namespace App\Services\Ocr;

class ProgressDocumentExtractor
{

    public function extract(string $text): array
    {
        preg_match('/(\d{1,3}(?:[,.]\d{1,2})?)\s*%/', $text, $match);
        preg_match('/(?:nomor|no\.?|document)\s*[:#-]?\s*([A-Z0-9\/.-]{4,80})/i', $text, $numberMatch);
        preg_match('/(?:tanggal|date)\s*[:#-]?\s*([0-9]{1,2}[-\/ ][A-Za-z0-9]{1,12}[-\/ ]20\d{2})/i', $text, $dateMatch);

        $percent = isset($match[1])
            ? min(100, max(0, (float) str_replace(',', '.', $match[1])))
            : null;

        return [
            'document_number' => $numberMatch[1] ?? null,
            'progress_percent' => $percent,
            'report_date' => $dateMatch[1] ?? null,
            'signatures_detected' => [
                'internal' => (bool) preg_match('/(?:internal|pelaksana|kontraktor|jasa tirta energi|jte)/i', $text),
                'owner' => (bool) preg_match('/(?:owner|pemilik|pengguna jasa|ppk|jasa tirta i)/i', $text),
            ],
        ];
    }
}
