<?php

namespace App\Http\Controllers\ProjectMonitoring;

class RapItemsController extends TableCrudController
{
    protected string $table = 'rap_items';

    protected function storeRules(): array
    {
        return [
            'rap_id' => ['required', 'integer', 'exists:raps,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'spec_brand' => ['nullable', 'string', 'max:100'],
            'spec_size' => ['nullable', 'string', 'max:100'],
            'spec_strength' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'rap_id' => ['sometimes', 'required', 'integer', 'exists:raps,id'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'quantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'unit_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'total_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'spec_brand' => ['sometimes', 'nullable', 'string', 'max:100'],
            'spec_size' => ['sometimes', 'nullable', 'string', 'max:100'],
            'spec_strength' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    protected function usesTimestamps(): bool
    {
        return false;
    }
}
