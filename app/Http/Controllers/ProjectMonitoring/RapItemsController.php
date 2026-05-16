<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Models\Rap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RapItemsController extends TableCrudController
{
    protected string $table = 'rap_items';

    protected function storeRules(): array
    {
        return [
            'rap_id' => ['required', 'integer', 'exists:raps,id'],
            'category' => ['nullable', 'string', 'max:150'],
            'sub_category' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
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
            'category' => ['sometimes', 'nullable', 'string', 'max:150'],
            'sub_category' => ['sometimes', 'nullable', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:50'],
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

    protected function prepareForStore(array $validated, Request $request): array
    {
        return $this->withCalculatedTotal($validated);
    }

    protected function prepareForUpdate(array $validated, Request $request, Model $record): array
    {
        return $this->withCalculatedTotal($validated);
    }

    protected function afterStore($record, Request $request): void
    {
        $this->syncTotal((int) $record->rap_id);
    }

    protected function afterUpdate($record, Request $request): void
    {
        $this->syncTotal((int) $record->rap_id);
    }

    protected function afterDestroy($record): void
    {
        $this->syncTotal((int) $record->rap_id);
    }

    private function syncTotal(int $rapId): void
    {
        $rap = Rap::query()->find($rapId);

        if ($rap) {
            $rap->update(['total_budget' => $rap->items()->sum('total_price')]);
        }
    }

    private function withCalculatedTotal(array $validated): array
    {
        if (($validated['total_price'] ?? null) === null && isset($validated['quantity'], $validated['unit_price'])) {
            $validated['total_price'] = (float) $validated['quantity'] * (float) $validated['unit_price'];
        }

        return $validated;
    }
}
