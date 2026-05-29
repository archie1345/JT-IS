<?php

namespace App\Services\Ocr;

class BudgetLineItemExtractor
{
    /**
     * @return list<array<string, mixed>>
     */
    public function extract(string $text): array
    {
        $items = [];

        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $line = trim((string) $line);

            if ($line === '' || count($items) >= 25) {
                continue;
            }

            if (! preg_match('/^(.+?)\s{2,}([A-Za-z0-9%\/.-]+)\s+([\d.,]+)\s+([\d.,]+)\s+([\d.,]+)$/', $line, $match)) {
                continue;
            }

            $items[] = [
                'description' => substr(trim($match[1]), 0, 255),
                'unit' => substr(trim($match[2]), 0, 50),
                'quantity' => $this->parseNumber($match[3]),
                'unit_price' => $this->parseNumber($match[4]),
                'total_price' => $this->parseNumber($match[5]),
                'confidence' => 0.45,
                'source_text' => $line,
            ];
        }

        return $items;
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
