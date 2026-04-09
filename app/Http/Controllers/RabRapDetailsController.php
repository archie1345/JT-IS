<?php

namespace App\Http\Controllers;

use App\Models\Rab;
use App\Models\Rap;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class RabRapDetailsController extends Controller
{
    public function showRab(Rab $rab): Response
    {
        return $this->renderDetails(
            kind: 'rab',
            title: 'RAB Detail',
            recordLabel: 'RAB',
            projectName: $rab->project?->name ?? '-',
            recordId: $rab->id,
            totalBudget: (float) ($rab->total_budget ?? 0),
            createdAt: $rab->created_at ? Carbon::parse($rab->created_at)->format('Y-m-d') : null,
            updatedAt: $rab->updated_at ? Carbon::parse($rab->updated_at)->format('Y-m-d') : null,
            items: $rab->items()
                ->orderBy('id')
                ->get()
                ->map(fn ($item): array => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit' => '-',
                    'unitPrice' => (float) $item->unit_price,
                    'totalPrice' => (float) $item->total_price,
                    'specBrand' => null,
                    'specSize' => null,
                    'specStrength' => null,
                ])
                ->all()
        );
    }

    public function showRap(Rap $rap): Response
    {
        return $this->renderDetails(
            kind: 'rap',
            title: 'RAP Detail',
            recordLabel: 'RAP',
            projectName: $rap->project?->name ?? '-',
            recordId: $rap->id,
            totalBudget: (float) ($rap->total_budget ?? 0),
            createdAt: $rap->created_at ? Carbon::parse($rap->created_at)->format('Y-m-d') : null,
            updatedAt: $rap->updated_at ? Carbon::parse($rap->updated_at)->format('Y-m-d') : null,
            items: $rap->items()
                ->orderBy('id')
                ->get()
                ->map(fn ($item): array => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit' => '-',
                    'unitPrice' => (float) $item->unit_price,
                    'totalPrice' => (float) $item->total_price,
                    'specBrand' => $item->spec_brand,
                    'specSize' => $item->spec_size,
                    'specStrength' => $item->spec_strength,
                ])
                ->all()
        );
    }

    protected function renderDetails(
        string $kind,
        string $title,
        string $recordLabel,
        string $projectName,
        int $recordId,
        float $totalBudget,
        ?string $createdAt,
        ?string $updatedAt,
        array $items,
    ): Response {
        $subtotal = array_sum(array_map(fn (array $item): float => (float) ($item['totalPrice'] ?? 0), $items));

        return Inertia::render('RabRapDetails', [
            'kind' => $kind,
            'title' => $title,
            'recordLabel' => $recordLabel,
            'record' => [
                'id' => $recordId,
                'projectName' => $projectName,
                'totalBudget' => $totalBudget,
                'itemCount' => count($items),
                'createdAt' => $createdAt,
                'updatedAt' => $updatedAt,
            ],
            'items' => $items,
            'summary' => [
                'subtotal' => $subtotal,
                'itemCount' => count($items),
                'difference' => $totalBudget - $subtotal,
            ],
        ]);
    }
}
