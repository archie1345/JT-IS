<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Http\Controllers\ProjectDocumentsController;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InvoicesController extends TableCrudController
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
        return 'Invoices';
    }

    protected function indexQuery(Request $request): Builder
    {
        return Invoice::query()
            ->with(['project:id,client_id,name', 'project.client:id,name'])
            ->orderByDesc('invoice_date')
            ->orderByDesc('id');
    }

    protected function transformRecord(Model $record, Request $request): array
    {
        /** @var Invoice $record */
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
                    'label' => $project->name ?? 'Untitled project',
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

    public function show(int $id): Response
    {
        $record = Invoice::query()
            ->with(['project:id,client_id,name', 'project.client:id,name'])
            ->findOrFail($id);

        return Inertia::render('PrototypeRecordDetails', [
            'title' => 'Billing Detail',
            'subtitle' => $record->invoice_number ? 'Invoice '.$record->invoice_number : 'Invoice #'.$record->id,
            'indexUrl' => route('invoices.index'),
            'updateUrl' => route('invoices.update', $record->id),
            'breadcrumbs' => [
                ['title' => 'Billing', 'href' => route('invoices.index')],
                ['title' => 'Invoice #'.$record->id, 'href' => route('invoices.show', $record->id)],
            ],
            'record' => $this->transformRecord($record, request()),
            'fields' => $this->detailFields(),
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

    protected function detailFields(): array
    {
        return [
            ['name' => 'project_id', 'label' => 'Project', 'type' => 'select', 'required' => true, 'options' => $this->projectOptions()],
            ['name' => 'invoice_number', 'label' => 'Invoice Number', 'type' => 'text'],
            ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
            ['name' => 'tax_amount', 'label' => 'Tax Amount', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
            ['name' => 'invoice_date', 'label' => 'Invoice Date', 'type' => 'date'],
            ['name' => 'due_date', 'label' => 'Due Date', 'type' => 'date'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'paid', 'label' => 'Paid'],
                ['value' => 'overdue', 'label' => 'Overdue'],
            ]],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
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
                'label' => $project->name ?? 'Untitled project',
                'hint' => $project->client?->name,
            ])
            ->all();
    }
}
