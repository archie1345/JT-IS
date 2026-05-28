<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Http\Controllers\ProjectDocumentsController;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\Tender;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TendersController extends TableCrudController
{
    protected string $model = Tender::class;

    protected function storeRules(): array
    {
        return [
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:200'],
            'document_number' => ['nullable', 'string', 'max:120'],
            'document_date' => ['nullable', 'date'],
            'owner' => ['nullable', 'string', 'max:200'],
            'location' => ['nullable', 'string'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['open', 'submitted', 'won', 'lost'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'project_id' => ['sometimes', 'nullable', 'integer', 'exists:projects,id'],
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:120'],
            'document_date' => ['sometimes', 'nullable', 'date'],
            'owner' => ['sometimes', 'nullable', 'string', 'max:200'],
            'location' => ['sometimes', 'nullable', 'string'],
            'value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'nullable', Rule::in(['open', 'submitted', 'won', 'lost'])],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    protected function inertiaView(): ?string
    {
        return 'Pipeline';
    }

    protected function indexQuery(Request $request): Builder
    {
        $projectId = $request->integer('project');

        return Tender::query()
            ->when($projectId > 0, fn (Builder $query) => $query->where('project_id', $projectId))
            ->with(['project:id,client_id,name', 'project.client:id,name'])
            ->orderByDesc('id');
    }

    protected function transformRecord(Model $record, Request $request): array
    {
        /** @var Tender $record */
        return [
            'id' => $record->id,
            'project_id' => $record->project_id,
            'project_name' => $record->project?->name,
            'client_name' => $record->project?->client?->name,
            'title' => $record->title,
            'document_number' => $record->document_number,
            'document_date' => optional($record->document_date)->format('Y-m-d'),
            'owner' => $record->owner,
            'location' => $record->location,
            'value' => $record->value !== null ? (float) $record->value : null,
            'status' => $record->status,
            'notes' => $record->notes,
            'created_at' => optional($record->created_at)->format('Y-m-d'),
            'can_convert' => $record->status === 'won' && $record->project_id === null ? 1 : 0,
        ];
    }

    public function convertToProject(int $id): RedirectResponse
    {
        $tender = Tender::query()->findOrFail($id);

        if ($tender->status !== 'won') {
            return redirect()->back()->withErrors([
                'status' => 'Only won tenders can be converted to projects.',
            ]);
        }

        if ($tender->project_id !== null) {
            return to_route('projects.show', $tender->project_id)
                ->with('success', 'Tender is already connected to a project.');
        }

        if (blank($tender->title)) {
            return redirect()->back()->withErrors([
                'title' => 'Tender title is required before conversion.',
            ]);
        }

        $project = DB::transaction(function () use ($id): Project {
            $tender = Tender::query()->lockForUpdate()->findOrFail($id);

            if ($tender->project_id !== null) {
                return $tender->project;
            }

            $clientId = null;

            if (filled($tender->owner)) {
                $client = Client::query()->firstOrCreate(
                    ['name' => $tender->owner],
                    ['contact' => null],
                );
                $clientId = $client->id;
            }

            $project = Project::query()->create([
                'client_id' => $clientId,
                'name' => $tender->title,
                'contract_value' => $tender->value ?? 0,
                'location' => $tender->location,
                'status' => 'planning',
            ]);

            $tender->update(['project_id' => $project->id]);

            return $project;
        });

        return to_route('projects.show', $project)
            ->with('success', 'Won tender converted to an active project.');
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
                ->where('component_type', 'pipeline')
                ->latest()
                ->limit(25)
                ->get()
                ->map(fn (ProjectDocument $document): array => ProjectDocumentsController::serialize($document))
                ->all(),
        ];
    }

    public function show(int $id): Response
    {
        $record = Tender::query()
            ->with(['project:id,client_id,name', 'project.client:id,name'])
            ->findOrFail($id);

        return Inertia::render('PrototypeRecordDetails', [
            'title' => 'Report Detail',
            'subtitle' => $record->title,
            'indexUrl' => route('pipeline'),
            'updateUrl' => route('pipeline.update', $record->id),
            'breadcrumbs' => [
                ['title' => 'Reports', 'href' => route('pipeline')],
                ['title' => 'Report #'.$record->id, 'href' => route('pipeline.show', $record->id)],
            ],
            'record' => $this->transformRecord($record, request()),
            'fields' => $this->detailFields(),
            'upload' => [
                'componentType' => 'pipeline',
                'componentId' => $record->id,
                'projectId' => $record->project_id,
                'documents' => ProjectDocument::query()
                    ->with('project:id,name')
                    ->where('component_type', 'pipeline')
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
            ['name' => 'project_id', 'label' => 'Project', 'type' => 'select', 'options' => $this->projectOptions()],
            ['name' => 'document_number', 'label' => 'Document Number', 'type' => 'text', 'placeholder' => 'Example: 001/SPH/JTE/II/2026'],
            ['name' => 'document_date', 'label' => 'Document Date', 'type' => 'date'],
            ['name' => 'title', 'label' => 'Work / Package Title', 'type' => 'text'],
            ['name' => 'owner', 'label' => 'Owner / Client', 'type' => 'text'],
            ['name' => 'location', 'label' => 'Location', 'type' => 'textarea'],
            ['name' => 'value', 'label' => 'Offer / Contract Value', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => [
                ['value' => 'open', 'label' => 'Open'],
                ['value' => 'submitted', 'label' => 'Submitted'],
                ['value' => 'won', 'label' => 'Won'],
                ['value' => 'lost', 'label' => 'Lost'],
            ]],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
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
