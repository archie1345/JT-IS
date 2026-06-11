<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceItemsController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $invoice->items()->create($this->validated($request));
        $this->syncTotal($invoice);

        return redirect()->back()->with('success', 'Item invoice berhasil disimpan.');
    }

    public function update(Request $request, int $id)
    {
        $item = InvoiceItem::query()->findOrFail($id);
        $item->update($this->validated($request));
        $this->syncTotal($item->invoice);

        return redirect()->back()->with('success', 'Item invoice berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        $item = InvoiceItem::query()->findOrFail($id);
        $invoice = $item->invoice;
        $item->delete();
        $this->syncTotal($invoice);

        return redirect()->back()->with('success', 'Item invoice berhasil dihapus.');
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

    protected function syncTotal(Invoice $invoice): void
    {
        $invoice->amount = $invoice->items()->sum('total_price');
        $invoice->save();
    }
}
