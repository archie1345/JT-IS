<?php

namespace App\Http\Controllers;

use App\Models\Rab;
use App\Models\Rap;
use App\Models\ProjectDocument;
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
            projectId: $rab->project_id,
            recordId: $rab->id,
            documentNumber: $rab->document_number,
            documentDate: $rab->document_date ? Carbon::parse($rab->document_date)->format('Y-m-d') : null,
            totalBudget: (float) ($rab->total_budget ?? 0),
            notes: $rab->notes,
            createdAt: $rab->created_at ? Carbon::parse($rab->created_at)->format('Y-m-d') : null,
            updatedAt: $rab->updated_at ? Carbon::parse($rab->updated_at)->format('Y-m-d') : null,
            items: $rab->items()
                ->orderBy('id')
                ->get()
                ->map(fn ($item): array => [
                    'id' => $item->id,
                    'category' => $item->category,
                    'subCategory' => $item->sub_category,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit ?? '-',
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
            projectId: $rap->project_id,
            recordId: $rap->id,
            documentNumber: $rap->document_number,
            documentDate: $rap->document_date ? Carbon::parse($rap->document_date)->format('Y-m-d') : null,
            totalBudget: (float) ($rap->total_budget ?? 0),
            notes: $rap->notes,
            createdAt: $rap->created_at ? Carbon::parse($rap->created_at)->format('Y-m-d') : null,
            updatedAt: $rap->updated_at ? Carbon::parse($rap->updated_at)->format('Y-m-d') : null,
            items: $rap->items()
                ->orderBy('id')
                ->get()
                ->map(fn ($item): array => [
                    'id' => $item->id,
                    'category' => $item->category,
                    'subCategory' => $item->sub_category,
                    'description' => $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit ?? '-',
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
        int $projectId,
        int $recordId,
        ?string $documentNumber,
        ?string $documentDate,
        float $totalBudget,
        ?string $notes,
        ?string $createdAt,
        ?string $updatedAt,
        array $items,
    ): Response {
        $subtotal = array_sum(array_map(fn (array $item): float => (float) ($item['totalPrice'] ?? 0), $items));
        $uploadedDocuments = ProjectDocument::query()
            ->with('project:id,name')
            ->where('project_id', $projectId)
            ->where('component_type', $kind)
            ->where('component_id', $recordId)
            ->latest()
            ->get()
            ->map(fn (ProjectDocument $document): array => ProjectDocumentsController::serialize($document))
            ->all();

        return Inertia::render('RabRapDetails', [
            'kind' => $kind,
            'title' => $title,
            'recordLabel' => $recordLabel,
            'record' => [
                'id' => $recordId,
                'projectId' => $projectId,
                'projectName' => $projectName,
                'document_number' => $documentNumber,
                'document_date' => $documentDate,
                'totalBudget' => $totalBudget,
                'notes' => $notes,
                'itemCount' => count($items),
                'createdAt' => $createdAt,
                'updatedAt' => $updatedAt,
            ],
            'items' => $items,
            'uploadedDocuments' => $uploadedDocuments,
            'summary' => [
                'subtotal' => $subtotal,
                'itemCount' => count($items),
                'difference' => $totalBudget - $subtotal,
            ],
        ]);
    }
}
