<?php

namespace App\Http\Controllers;

use App\Models\Rab;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RabsPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $projectId = $request->integer('project');

        $paginator = Rab::query()
            ->when($projectId > 0, fn ($query) => $query->where('project_id', $projectId))
            ->with('project:id,name')
            ->withCount('items')
            ->withSum('items as computed_total_budget', 'total_price')
            ->latest('id')
            ->paginate($this->perPageFromRequest($request))
            ->withQueryString();

        $rabs = $paginator->getCollection()
            ->map(fn (Rab $rab): array => [
                'id' => $rab->id,
                'project_id' => $rab->project_id,
                'document_number' => $rab->document_number,
                'document_date' => optional($rab->document_date)->format('Y-m-d'),
                'total_budget' => (float) ($rab->computed_total_budget ?? 0),
                'notes' => $rab->notes,
                'projectId' => $rab->project_id,
                'projectName' => $rab->project?->name ?? '-',
                'totalBudget' => (float) ($rab->computed_total_budget ?? 0),
                'itemCount' => $rab->items_count,
                'createdAt' => optional($rab->created_at)->format('Y-m-d'),
                'updatedAt' => optional($rab->updated_at)->format('Y-m-d'),
            ])
            ->all();

        return Inertia::render('Rabs', [
            'rabs' => $rabs,
            'pagination' => $this->paginationMeta($paginator),
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
                ->where('component_type', 'rab')
                ->when($projectId > 0, fn ($query) => $query->where('project_id', $projectId))
                ->latest()
                ->limit(25)
                ->get()
                ->map(fn (ProjectDocument $document): array => ProjectDocumentsController::serialize($document))
                ->all(),
        ]);
    }
}
