<?php

namespace App\Services\Ocr;

class InvoiceDocumentExtractor
{

    public function extract(string $text): array
    {
        preg_match('/(?:invoice|tagihan|no\.?)\s*[:#-]?\s*([A-Z0-9\/.-]{4,80})/i', $text, $match);
        preg_match('/(?:tanggal|invoice date)\s*[:#-]?\s*([0-9]{1,2}[-\/ ][A-Za-z0-9]{1,12}[-\/ ]20\d{2})/i', $text, $dateMatch);
        preg_match('/(?:jatuh tempo|due date)\s*[:#-]?\s*([0-9]{1,2}[-\/ ][A-Za-z0-9]{1,12}[-\/ ]20\d{2})/i', $text, $dueDateMatch);
        preg_match('/(?:total|jumlah|amount|nilai)\s*[:#-]?\s*(?:Rp\.?\s*)?([\d.,]+)/i', $text, $amountMatch);

        return [
            'invoice_number' => $match[1] ?? null,
            'invoice_date' => $dateMatch[1] ?? null,
            'due_date' => $dueDateMatch[1] ?? null,
            'amount' => isset($amountMatch[1]) ? $this->parseNumber($amountMatch[1]) : null,
        ];
    }

    protected function parseNumber(string $value): ?float
    {
        $cleaned = preg_replace('/[^\d,.-]/', '', $value) ?? '';

        if (! preg_match('/\d/', $cleaned)) {
            return null;
        }

        $lastComma = strrpos($cleaned, ',');
        $lastDot = strrpos($cleaned, '.');
        $decimalIndex = max($lastComma === false ? -1 : $lastComma, $lastDot === false ? -1 : $lastDot);
        $hasDecimal = $decimalIndex >= 0 && strlen(substr($cleaned, $decimalIndex + 1)) <= 2;
        $normalized = $hasDecimal
            ? str_replace([',', '.'], '', substr($cleaned, 0, $decimalIndex)).'.'.str_replace([',', '.'], '', substr($cleaned, $decimalIndex + 1))
            : str_replace([',', '.'], '', $cleaned);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
