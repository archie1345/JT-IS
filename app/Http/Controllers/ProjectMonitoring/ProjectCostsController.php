<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Http\Controllers\ProjectDocumentsController;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\ProjectDocument;
use App\Models\Rab;
use App\Models\RabItem;
use App\Models\Rap;
use App\Models\RapItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectCostsController extends CrudResourceController
{
    protected string $model = ProjectCost::class;

    protected function storeRules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:100'],
            'vendor' => ['nullable', 'string', 'max:200'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'reference_number' => ['sometimes', 'nullable', 'string', 'max:120'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'vendor' => ['sometimes', 'nullable', 'string', 'max:200'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'date' => ['sometimes', 'nullable', 'date'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    protected function inertiaView(): ?string
    {
        return 'finance/costs/Index';
    }

    protected function indexQuery(Request $request): Builder
    {
        $projectId = $request->integer('project');

        return ProjectCost::query()
            ->when($projectId > 0, fn (Builder $query) => $query->where('project_id', $projectId))
            ->with('project:id,name')
            ->orderByDesc('date')
            ->orderByDesc('id');
    }

    protected function transformRecord(Model $record, Request $request): array
    {

        return [
            'id' => $record->id,
            'project_id' => $record->project_id,
            'project_name' => $record->project?->name,
            'reference_number' => $record->reference_number,
            'category' => $record->category,
            'vendor' => $record->vendor,
            'amount' => $record->amount !== null ? (float) $record->amount : null,
            'date' => optional($record->date)->format('Y-m-d'),
            'description' => $record->description,
        ];
    }

    protected function pageProps(Request $request): array
    {
        return [
            'projectOptions' => Project::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Project $project): array => [
                    'value' => $project->id,
                    'label' => $project->name ?? 'Proyek tanpa nama',
                ])
                ->all(),
            'uploadedDocuments' => ProjectDocument::query()
                ->with('project:id,name')
                ->where('component_type', 'project_cost')
                ->latest()
                ->limit(25)
                ->get()
                ->map(fn (ProjectDocument $document): array => ProjectDocumentsController::serialize($document))
                ->all(),
        ];
    }

    public function show(int $id): Response
    {
        $record = ProjectCost::query()
            ->with(['items', 'project:id,name'])
            ->findOrFail($id);

        return Inertia::render('finance/FinancialDocumentDetails', [
            'kind' => 'cost',
            'title' => 'Detail Realisasi Biaya',
            'recordLabel' => 'Realisasi Biaya',
            'indexUrl' => route('project-costs.index'),
            'updateUrl' => route('project-costs.update', $record->id),
            'itemStoreUrl' => route('project-costs.items.store', $record->id),
            'itemUpdateUrlBase' => url('project-cost-items'),
            'breadcrumbs' => [
                ['title' => 'Realisasi Biaya', 'href' => route('project-costs.index')],
                ['title' => 'Biaya #'.$record->id, 'href' => route('project-costs.show', $record->id)],
            ],
            'record' => $this->transformRecord($record, request()),
            'fields' => $this->detailFields(),
            'items' => $record->items
                ->sortBy('id')
                ->values()
                ->map(fn ($item): array => [
                    'id' => $item->id,
                    'sourceType' => $item->source_type,
                    'sourceItemId' => $item->source_item_id,
                    'category' => $item->category,
                    'description' => $item->description,
                    'unit' => $item->unit,
                    'quantity' => (float) ($item->quantity ?? 0),
                    'unitPrice' => (float) ($item->unit_price ?? 0),
                    'totalPrice' => (float) ($item->total_price ?? 0),
                    'vendor' => $item->vendor,
                    'notes' => $item->notes,
                ])
                ->all(),
            'budgetItemOptions' => $this->budgetItemOptions($record->project_id),
            'summary' => [
                'subtotal' => (float) $record->items->sum('total_price'),
                'tax' => 0,
                'total' => (float) $record->items->sum('total_price'),
                'itemCount' => $record->items->count(),
            ],
            'upload' => [
                'componentType' => 'project_cost',
                'componentId' => $record->id,
                'projectId' => $record->project_id,
                'documents' => ProjectDocument::query()
                    ->with('project:id,name')
                    ->where('component_type', 'project_cost')
                    ->where('component_id', $record->id)
                    ->latest()
                    ->get()
                    ->map(fn (ProjectDocument $document): array => ProjectDocumentsController::serialize($document))
                    ->all(),
            ],
        ]);
    }

    protected function budgetItemOptions(int $projectId): array
    {
        $rabItems = Rab::query()
            ->where('project_id', $projectId)
            ->with([
                'items' => fn ($query) => $query->orderBy('id'),
            ])
            ->latest('id')
            ->get()
            ->flatMap(function (Rab $rab): array {
                return $rab->items->map(fn (RabItem $item): array => [
                    'value' => 'rab:'.$rab->id.':'.$item->id,
                    'sourceType' => 'rab',
                    'sourceItemId' => $item->id,
                    'label' => trim(implode(' - ', array_filter([
                        $rab->document_number ?: 'RAB #'.$rab->id,
                        $item->description ?: $item->category ?: 'Item',
                    ]))),
                    'hint' => trim(implode(' / ', array_filter([
                        optional($rab->document_date)->format('Y-m-d'),
                        $item->sub_category ?: $item->category,
                    ]))),
                    'category' => $item->category ?: $rab->document_number ?: 'RAB',
                    'description' => $item->description ?: $item->sub_category ?: $item->category ?: 'Item RAB',
                    'unit' => $item->unit ?: 'ls',
                    'quantity' => (float) ($item->quantity ?? 0),
                    'unitPrice' => (float) ($item->unit_price ?? 0),
                    'totalPrice' => (float) ($item->total_price ?? 0),
                    'documentNumber' => $rab->document_number,
                    'documentDate' => optional($rab->document_date)->format('Y-m-d'),
                    'itemCount' => 1,
                    'totalBudget' => (float) ($item->total_price ?? 0),
                ])->all();
            })
            ->values()
            ->all();

        $rapItems = Rap::query()
            ->where('project_id', $projectId)
            ->with([
                'items' => fn ($query) => $query->orderBy('id'),
            ])
            ->latest('id')
            ->get()
            ->flatMap(function (Rap $rap): array {
                return $rap->items->map(fn (RapItem $item): array => [
                    'value' => 'rap:'.$rap->id.':'.$item->id,
                    'sourceType' => 'rap',
                    'sourceItemId' => $item->id,
                    'label' => trim(implode(' - ', array_filter([
                        $rap->document_number ?: 'RAP #'.$rap->id,
                        $item->description ?: $item->category ?: 'Item',
                    ]))),
                    'hint' => trim(implode(' / ', array_filter([
                        optional($rap->document_date)->format('Y-m-d'),
                        $item->sub_category ?: $item->category,
                    ]))),
                    'category' => $item->category ?: $rap->document_number ?: 'RAP',
                    'description' => $item->description ?: $item->sub_category ?: $item->category ?: 'Item RAP',
                    'unit' => $item->unit ?: 'ls',
                    'quantity' => (float) ($item->quantity ?? 0),
                    'unitPrice' => (float) ($item->unit_price ?? 0),
                    'totalPrice' => (float) ($item->total_price ?? 0),
                    'documentNumber' => $rap->document_number,
                    'documentDate' => optional($rap->document_date)->format('Y-m-d'),
                    'itemCount' => 1,
                    'totalBudget' => (float) ($item->total_price ?? 0),
                ])->all();
            })
            ->values()
            ->all();

        return array_merge($rabItems, $rapItems);
    }

    protected function detailFields(): array
    {
        return [
            ['name' => 'project_id', 'label' => 'Proyek', 'type' => 'select', 'required' => true, 'options' => $this->projectOptions()],
            ['name' => 'reference_number', 'label' => 'Nomor Referensi', 'type' => 'text'],
            ['name' => 'category', 'label' => 'Kategori', 'type' => 'text'],
            ['name' => 'vendor', 'label' => 'Vendor / Penerima', 'type' => 'text'],
            ['name' => 'amount', 'label' => 'Nilai', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
            ['name' => 'date', 'label' => 'Tanggal', 'type' => 'date'],
            ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
        ];
    }

    protected function projectOptions(): array
    {
        return Project::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Project $project): array => [
                'value' => $project->id,
                'label' => $project->name ?? 'Proyek tanpa nama',
            ])
            ->all();
    }
}
