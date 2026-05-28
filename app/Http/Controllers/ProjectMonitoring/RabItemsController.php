<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Models\Rab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RabItemsController extends CrudResourceController
{
    protected string $table = 'rab_items';

    protected function storeRules(): array
    {
        return [
            'rab_id' => ['required', 'integer', 'exists:rabs,id'],
            'category' => ['nullable', 'string', 'max:150'],
            'sub_category' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'rab_id' => ['sometimes', 'required', 'integer', 'exists:rabs,id'],
            'category' => ['sometimes', 'nullable', 'string', 'max:150'],
            'sub_category' => ['sometimes', 'nullable', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'quantity' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'unit_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'total_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
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
        $this->syncTotal((int) $record->rab_id);
    }

    protected function afterUpdate($record, Request $request): void
    {
        $this->syncTotal((int) $record->rab_id);
    }

    protected function afterDestroy($record): void
    {
        $this->syncTotal((int) $record->rab_id);
    }

    private function syncTotal(int $rabId): void
    {
        $rab = Rab::query()->find($rabId);

        if ($rab) {
            $rab->update(['total_budget' => $rab->items()->sum('total_price')]);
        }
    }

    private function withCalculatedTotal(array $validated): array
    {
        if (isset($validated['quantity'], $validated['unit_price'])) {
            $validated['total_price'] = (float) $validated['quantity'] * (float) $validated['unit_price'];
        }

        return $validated;
    }
}
