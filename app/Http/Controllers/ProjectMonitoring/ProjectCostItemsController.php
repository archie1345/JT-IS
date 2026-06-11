<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Http\Controllers\Controller;
use App\Models\ProjectCost;
use App\Models\ProjectCostItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectCostItemsController extends Controller
{
    public function store(Request $request, ProjectCost $projectCost)
    {
        $projectCost->items()->create($this->validated($request));
        $this->syncTotal($projectCost);

        return redirect()->back()->with('success', 'Item biaya berhasil disimpan.');
    }

    public function update(Request $request, int $id)
    {
        $item = ProjectCostItem::query()->findOrFail($id);
        $item->update($this->validated($request));
        $this->syncTotal($item->projectCost);

        return redirect()->back()->with('success', 'Item biaya berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        $item = ProjectCostItem::query()->findOrFail($id);
        $projectCost = $item->projectCost;
        $item->delete();
        $this->syncTotal($projectCost);

        return redirect()->back()->with('success', 'Item biaya berhasil dihapus.');
    }

    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'source_type' => ['nullable', Rule::in(['rab', 'rap', 'manual'])],
            'source_item_id' => ['nullable', 'integer'],
            'category' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'vendor' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string'],
        ]);

        return $this->withComputedTotal($validated);
    }

    protected function withComputedTotal(array $validated): array
    {
        $quantity = (float) ($validated['quantity'] ?? 0);
        $unitPrice = (float) ($validated['unit_price'] ?? 0);

        if (($validated['total_price'] ?? null) === null && $quantity > 0 && $unitPrice > 0) {
            $validated['total_price'] = $quantity * $unitPrice;
        }

        return $validated;
    }

    protected function syncTotal(ProjectCost $projectCost): void
    {
        $projectCost->amount = $projectCost->items()->sum('total_price');
        $projectCost->save();
    }
}
