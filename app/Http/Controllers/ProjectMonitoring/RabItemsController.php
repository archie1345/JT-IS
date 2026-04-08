<?php

namespace App\Http\Controllers\ProjectMonitoring;

class RabItemsController extends TableCrudController
{
    protected string $table = 'rab_items';

    protected function storeRules(): array
    {
        return [
            'rab_id' => ['required', 'integer', 'exists:rabs,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'rab_id' => ['sometimes', 'required', 'integer', 'exists:rabs,id'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'quantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'unit_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'total_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }

    protected function usesTimestamps(): bool
    {
        return false;
    }
}
