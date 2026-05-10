<?php

namespace App\Http\Controllers\ProjectMonitoring;

use App\Models\ProgressReport;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ProgressReportsController extends TableCrudController
{
    protected string $model = ProgressReport::class;

    protected function storeRules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'report_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'progress_percent' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'report_date' => ['sometimes', 'nullable', 'date'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    protected function inertiaView(): ?string
    {
        return 'ProgressUpdates';
    }

    protected function indexQuery(Request $request): Builder
    {
        return ProgressReport::query()
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
            'progress_percent' => $record->progress_percent,
            'report_date' => optional($record->report_date)->format('Y-m-d'),
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
        ];
    }
}
