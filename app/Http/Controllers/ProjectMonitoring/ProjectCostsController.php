<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Http\Controllers\ProjectDocumentsController;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\ProjectDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectCostsController extends TableCrudController
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
        return 'ProjectCosts';
    }

    protected function indexQuery(Request $request): Builder
    {
        return ProjectCost::query()
            ->with(['project:id,client_id,name', 'project.client:id,name'])
            ->orderByDesc('date')
            ->orderByDesc('id');
    }

    protected function transformRecord(Model $record, Request $request): array
    {
        /** @var ProjectCost $record */
        return [
            'id' => $record->id,
            'project_id' => $record->project_id,
            'project_name' => $record->project?->name,
            'client_name' => $record->project?->client?->name,
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
            ->with(['project:id,client_id,name', 'project.client:id,name'])
            ->findOrFail($id);

        return Inertia::render('PrototypeRecordDetails', [
            'title' => 'Cost Realization Detail',
            'subtitle' => $record->category,
            'indexUrl' => route('project-costs.index'),
            'updateUrl' => route('project-costs.update', $record->id),
            'breadcrumbs' => [
                ['title' => 'Cost Realization', 'href' => route('project-costs.index')],
                ['title' => 'Cost #'.$record->id, 'href' => route('project-costs.show', $record->id)],
            ],
            'record' => $this->transformRecord($record, request()),
            'fields' => $this->detailFields(),
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

    protected function detailFields(): array
    {
        return [
            ['name' => 'project_id', 'label' => 'Project', 'type' => 'select', 'required' => true, 'options' => $this->projectOptions()],
            ['name' => 'reference_number', 'label' => 'Reference Number', 'type' => 'text'],
            ['name' => 'category', 'label' => 'Category', 'type' => 'text'],
            ['name' => 'vendor', 'label' => 'Vendor / Payee', 'type' => 'text'],
            ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
            ['name' => 'date', 'label' => 'Date', 'type' => 'date'],
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
