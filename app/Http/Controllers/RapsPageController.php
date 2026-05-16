<?php

namespace App\Http\Controllers;

use App\Models\Rap;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RapsPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $projectId = $request->integer('project');

        $raps = Rap::query()
            ->when($projectId > 0, fn ($query) => $query->where('project_id', $projectId))
            ->with('project:id,name')
            ->withCount('items')
            ->latest('id')
            ->get()
            ->map(fn (Rap $rap): array => [
                'id' => $rap->id,
                'project_id' => $rap->project_id,
                'total_budget' => (float) ($rap->total_budget ?? 0),
                'projectId' => $rap->project_id,
                'projectName' => $rap->project?->name ?? '-',
                'totalBudget' => (float) ($rap->total_budget ?? 0),
                'itemCount' => $rap->items_count,
                'createdAt' => optional($rap->created_at)->format('Y-m-d'),
                'updatedAt' => optional($rap->updated_at)->format('Y-m-d'),
            ])
            ->all();

        return Inertia::render('Raps', [
            'raps' => $raps,
            'activeProjectId' => $projectId > 0 ? $projectId : null,
            'projectOptions' => Project::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Project $project): array => [
                    'value' => $project->id,
                    'label' => $project->name ?? 'Project #'.$project->id,
                ])
                ->values(),
            'uploadedDocuments' => ProjectDocument::query()
                ->with('project:id,name')
                ->where('component_type', 'rap')
                ->when($projectId > 0, fn ($query) => $query->where('project_id', $projectId))
                ->latest()
                ->limit(25)
                ->get()
                ->map(fn (ProjectDocument $document): array => ProjectDocumentsController::serialize($document))
                ->all(),
        ]);
    }
}
