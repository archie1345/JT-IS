<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Http\Controllers\ProjectDocumentsController;
use App\Models\ProgressReport;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgressReportsController extends CrudResourceController
{
    protected string $model = ProgressReport::class;

    protected function storeRules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'document_number' => ['nullable', 'string', 'max:120'],
            'document_type' => ['nullable', 'string', 'max:80'],
            'progress_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'report_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'approved_by_client' => ['nullable', 'boolean'],
            'approved_by_internal' => ['nullable', 'boolean'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:120'],
            'document_type' => ['sometimes', 'nullable', 'string', 'max:80'],
            'progress_percent' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'period_start' => ['sometimes', 'nullable', 'date'],
            'period_end' => ['sometimes', 'nullable', 'date'],
            'report_date' => ['sometimes', 'nullable', 'date'],
            'description' => ['sometimes', 'nullable', 'string'],
            'approved_by_client' => ['sometimes', 'nullable', 'boolean'],
            'approved_by_internal' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    protected function inertiaView(): ?string
    {
        return 'progress/Index';
    }

    protected function indexQuery(Request $request): Builder
    {
        $projectId = $request->integer('project');

        return ProgressReport::query()
            ->when($projectId > 0, fn (Builder $query) => $query->where('project_id', $projectId))
            ->with(['project:id,client_id,name', 'project.client:id,name'])
            ->orderByDesc('report_date')
            ->orderByDesc('id');
    }

    protected function transformRecord(Model $record, Request $request): array
    {
        /** @var ProgressReport $record */
        return [
            'id' => $record->id,
            'project_id' => $record->project_id,
            'project_name' => $record->project?->name,
            'client_name' => $record->project?->client?->name,
            'document_number' => $record->document_number,
            'document_type' => $record->document_type,
            'progress_percent' => $record->progress_percent,
            'period_start' => optional($record->period_start)->format('Y-m-d'),
            'period_end' => optional($record->period_end)->format('Y-m-d'),
            'report_date' => optional($record->report_date)->format('Y-m-d'),
            'description' => $record->description,
            'approved_by_client' => $record->approved_by_client ? '1' : '0',
            'approved_by_internal' => $record->approved_by_internal ? '1' : '0',
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
                ->where('component_type', 'progress_report')
                ->latest()
                ->limit(25)
                ->get()
                ->map(fn (ProjectDocument $document): array => ProjectDocumentsController::serialize($document))
                ->all(),
        ];
    }

    public function show(int $id): Response
    {
        $record = ProgressReport::query()
            ->with(['project:id,client_id,name', 'project.client:id,name'])
            ->findOrFail($id);

        return Inertia::render('shared/RecordDetails', [
            'title' => 'Progress Detail',
            'subtitle' => trim(($record->progress_percent ?? 0).'% '.$record->project?->name),
            'indexUrl' => route('progress-updates.index'),
            'updateUrl' => route('progress-updates.update', $record->id),
            'breadcrumbs' => [
                ['title' => 'Progress Update', 'href' => route('progress-updates.index')],
                ['title' => 'Progress #'.$record->id, 'href' => route('progress-updates.show', $record->id)],
            ],
            'record' => $this->transformRecord($record, request()),
            'fields' => $this->detailFields(),
            'upload' => [
                'componentType' => 'progress_report',
                'componentId' => $record->id,
                'projectId' => $record->project_id,
                'documents' => ProjectDocument::query()
                    ->with('project:id,name')
                    ->where('component_type', 'progress_report')
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
            ['name' => 'document_number', 'label' => 'Document Number', 'type' => 'text'],
            ['name' => 'document_type', 'label' => 'Document Type', 'type' => 'select', 'options' => [
                ['value' => 'BA MC', 'label' => 'BA MC / Mutual Check'],
                ['value' => 'BAHPP', 'label' => 'BAHPP'],
                ['value' => 'C3', 'label' => 'C3'],
                ['value' => 'Laporan Akhir', 'label' => 'Laporan Akhir'],
            ]],
            ['name' => 'progress_percent', 'label' => 'Progress Percent', 'type' => 'select', 'options' => [
                ['value' => '0', 'label' => '0%'],
                ['value' => '25', 'label' => '25%'],
                ['value' => '50', 'label' => '50%'],
                ['value' => '75', 'label' => '75%'],
                ['value' => '100', 'label' => '100%'],
            ]],
            ['name' => 'period_start', 'label' => 'Period Start', 'type' => 'date'],
            ['name' => 'period_end', 'label' => 'Period End', 'type' => 'date'],
            ['name' => 'report_date', 'label' => 'Report Date', 'type' => 'date'],
            ['name' => 'approved_by_client', 'label' => 'Client Approval', 'type' => 'select', 'options' => [
                ['value' => '0', 'label' => 'No'],
                ['value' => '1', 'label' => 'Yes'],
            ]],
            ['name' => 'approved_by_internal', 'label' => 'Internal Approval', 'type' => 'select', 'options' => [
                ['value' => '0', 'label' => 'No'],
                ['value' => '1', 'label' => 'Yes'],
            ]],
            ['name' => 'description', 'label' => 'Summary', 'type' => 'textarea'],
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
