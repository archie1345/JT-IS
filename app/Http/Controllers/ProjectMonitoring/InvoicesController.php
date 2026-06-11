<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Http\Controllers\ProjectDocumentsController;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Rab;
use App\Models\Rap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InvoicesController extends CrudResourceController
{
    protected string $model = Invoice::class;

    protected function storeRules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'invoice_number' => ['nullable', 'string', 'max:120'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['pending', 'paid', 'overdue'])],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'invoice_number' => ['sometimes', 'nullable', 'string', 'max:120'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'invoice_date' => ['sometimes', 'nullable', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'nullable', Rule::in(['pending', 'paid', 'overdue'])],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    protected function inertiaView(): ?string
    {
        return 'finance/invoices/Index';
    }

    protected function indexQuery(Request $request): Builder
    {
        $projectId = $request->integer('project');

        return Invoice::query()
            ->when($projectId > 0, fn (Builder $query) => $query->where('project_id', $projectId))
            ->with(['project:id,client_id,name', 'project.client:id,name'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');
    }

    protected function transformRecord(Model $record, Request $request): array
    {

        return [
            'id' => $record->id,
            'project_id' => $record->project_id,
            'project_name' => $record->project?->name,
            'client_name' => $record->project?->client?->name,
            'invoice_number' => $record->invoice_number,
            'amount' => $record->amount !== null ? (float) $record->amount : null,
            'tax_amount' => $record->tax_amount !== null ? (float) $record->tax_amount : null,
            'invoice_date' => optional($record->invoice_date)->format('Y-m-d'),
            'due_date' => optional($record->due_date)->format('Y-m-d'),
            'status' => $record->status,
            'description' => $record->description,
        ];
    }

    protected function pageProps(Request $request): array
    {
        return [
            'projectOptions' => Project::query()
                ->with('client:id,name')
                ->orderBy('name')
                ->get(['id', 'client_id', 'name'])
                ->map(fn (Project $project): array => [
                    'value' => $project->id,
                    'label' => $project->name ?? 'Proyek tanpa nama',
                    'hint' => $project->client?->name,
                ])
                ->all(),
            'uploadedDocuments' => ProjectDocument::query()
                ->with('project:id,name')
                ->where('component_type', 'invoice')
                ->latest()
                ->limit(25)
                ->get()
                ->map(fn (ProjectDocument $document): array => ProjectDocumentsController::serialize($document))
                ->all(),
        ];
    }

    protected function prepareForStore(array $validated, Request $request): array
    {
        $this->validateBillableAmount($validated);

        return $validated;
    }

    protected function prepareForUpdate(array $validated, Request $request, Model $record): array
    {
        $payload = array_merge($record->only([
            'project_id',
            'amount',
            'invoice_number',
            'tax_amount',
            'invoice_date',
            'due_date',
            'status',
            'description',
        ]), $validated);

        $this->validateBillableAmount($payload, (int) $record->id);

        return $validated;
    }

    public function show(int $id): Response
    {
        $record = Invoice::query()
            ->with(['items', 'project:id,client_id,name', 'project.client:id,name'])
            ->findOrFail($id);

        return Inertia::render('finance/FinancialDocumentDetails', [
            'kind' => 'invoice',
            'title' => 'Detail Invoice',
            'recordLabel' => 'Invoice',
            'indexUrl' => route('invoices.index'),
            'updateUrl' => route('invoices.update', $record->id),
            'itemStoreUrl' => route('invoices.items.store', $record->id),
            'itemUpdateUrlBase' => url('invoice-items'),
            'breadcrumbs' => [
                ['title' => 'Invoice', 'href' => route('invoices.index')],
                ['title' => 'Invoice #'.$record->id, 'href' => route('invoices.show', $record->id)],
            ],
            'record' => $this->transformRecord($record, request()),
            'fields' => $this->detailFields(),
            'items' => $record->items()
                ->orderBy('id')
                ->get()
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
                    'vendor' => null,
                    'notes' => $item->notes,
                ])
                ->all(),
            'budgetItemOptions' => $this->budgetItemOptions($record->project_id),
            'summary' => [
                'subtotal' => (float) $record->items()->sum('total_price'),
                'tax' => (float) ($record->tax_amount ?? 0),
                'total' => (float) ($record->items()->sum('total_price') + ($record->tax_amount ?? 0)),
                'itemCount' => $record->items()->count(),
            ],
            'upload' => [
                'componentType' => 'invoice',
                'componentId' => $record->id,
                'projectId' => $record->project_id,
                'documents' => ProjectDocument::query()
                    ->with('project:id,name')
                    ->where('component_type', 'invoice')
                    ->where('component_id', $record->id)
                    ->latest()
                    ->get()
                    ->map(fn (ProjectDocument $document): array => ProjectDocumentsController::serialize($document))
                    ->all(),
            ],
        ]);
    }

    public function preview(Invoice $invoice): Response
    {
        $invoice->load([
            'items',
            'project:id,client_id,name',
            'project.client:id,name',
        ]);

        $items = $invoice->items()
            ->orderBy('id')
            ->get()
            ->map(fn ($item): array => [
                'id' => $item->id,
                'category' => $item->category,
                'description' => $item->description,
                'quantity' => (float) ($item->quantity ?? 0),
                'unit' => $item->unit,
                'unitPrice' => (float) ($item->unit_price ?? 0),
                'totalPrice' => (float) ($item->total_price ?? 0),
            ])
            ->all();

        if ($items === []) {
            $items = [[
                'description' => $invoice->description
                    ?: $invoice->project?->name
                    ?: 'Invoice proyek',
                'projectName' => $invoice->project?->name,
                'quantity' => 1,
                'unitPrice' => (float) ($invoice->amount ?? 0),
                'totalPrice' => (float) ($invoice->amount ?? 0),
            ]];
        }

        $subtotal = array_sum(array_map(
            fn (array $item): float => (float) ($item['totalPrice'] ?? 0),
            $items,
        ));

        return Inertia::render('finance/invoices/Preview', [
            'invoice' => $this->transformRecord($invoice, request()),
            'lineItems' => $items,
            'subtotal' => $subtotal,
            'tax' => (float) ($invoice->tax_amount ?? 0),
            'total' => $subtotal + (float) ($invoice->tax_amount ?? 0),
        ]);
    }

    protected function budgetItemOptions(int $projectId): array
    {
        $rabItems = Rab::query()
            ->where('project_id', $projectId)
            ->with('items')
            ->latest('id')
            ->get()
            ->flatMap(fn (Rab $rab) => $rab->items->map(fn ($item): array => [
                'value' => 'rab:'.$item->id,
                'sourceType' => 'rab',
                'sourceItemId' => $item->id,
                'label' => $item->description ?? 'RAB item #'.$item->id,
                'hint' => trim(implode(' / ', array_filter([$item->category, $item->sub_category]))),
                'category' => $item->category,
                'description' => $item->description,
                'unit' => $item->unit,
                'quantity' => (float) ($item->quantity ?? 0),
                'unitPrice' => (float) ($item->unit_price ?? 0),
                'totalPrice' => (float) ($item->total_price ?? 0),
            ]));

        $rapItems = Rap::query()
            ->where('project_id', $projectId)
            ->with('items')
            ->latest('id')
            ->get()
            ->flatMap(fn (Rap $rap) => $rap->items->map(fn ($item): array => [
                'value' => 'rap:'.$item->id,
                'sourceType' => 'rap',
                'sourceItemId' => $item->id,
                'label' => $item->description ?? 'RAP item #'.$item->id,
                'hint' => trim(implode(' / ', array_filter([$item->category, $item->sub_category]))),
                'category' => $item->category,
                'description' => $item->description,
                'unit' => $item->unit,
                'quantity' => (float) ($item->quantity ?? 0),
                'unitPrice' => (float) ($item->unit_price ?? 0),
                'totalPrice' => (float) ($item->total_price ?? 0),
            ]));

        return $rabItems->concat($rapItems)->values()->all();
    }

    protected function detailFields(): array
    {
        return [
            ['name' => 'project_id', 'label' => 'Proyek', 'type' => 'select', 'required' => true, 'options' => $this->projectOptions()],
            ['name' => 'invoice_number', 'label' => 'Nomor Invoice', 'type' => 'text'],
            ['name' => 'amount', 'label' => 'Nilai', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
            ['name' => 'tax_amount', 'label' => 'Nilai Pajak', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
            ['name' => 'invoice_date', 'label' => 'Tanggal Invoice', 'type' => 'date'],
            ['name' => 'due_date', 'label' => 'Jatuh Tempo', 'type' => 'date'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [
                ['value' => 'pending', 'label' => 'Menunggu'],
                ['value' => 'paid', 'label' => 'Lunas'],
                ['value' => 'overdue', 'label' => 'Terlambat'],
            ]],
            ['name' => 'description', 'label' => 'Deskripsi', 'type' => 'textarea'],
        ];
    }

    protected function projectOptions(): array
    {
        return Project::query()
            ->with('client:id,name')
            ->orderBy('name')
            ->get(['id', 'client_id', 'name'])
            ->map(fn (Project $project): array => [
                'value' => $project->id,
                'label' => $project->name ?? 'Proyek tanpa nama',
                'hint' => $project->client?->name,
            ])
            ->all();
    }

    private function validateBillableAmount(array $payload, ?int $exceptInvoiceId = null): void
    {
        $amount = (float) ($payload['amount'] ?? 0);

        if ($amount <= 0) {
            return;
        }

        $project = Project::query()->findOrFail((int) $payload['project_id']);
        $contractValue = (float) ($project->contract_value ?? 0);
        $approvedProgress = $project->latestApprovedProgressPercent();

        if ($contractValue <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Invoice tidak bisa dibuat: nilai kontrak proyek masih kosong.',
            ]);
        }

        if ($approvedProgress === null) {
            throw ValidationException::withMessages([
                'amount' => 'Invoice tidak bisa dibuat: proyek belum memiliki progress yang disetujui penuh.',
            ]);
        }

        $maxBillable = $contractValue * ($approvedProgress / 100);
        $remainingBillable = $maxBillable - $project->invoiceTotal($exceptInvoiceId);

        if ($amount > $remainingBillable) {
            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'Invoice melebihi progress tagihan yang disetujui. Sisa nilai yang bisa ditagih adalah Rp%s.',
                    number_format(max(0, $remainingBillable), 0, ',', '.'),
                ),
            ]);
        }
    }
}
