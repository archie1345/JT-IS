<?php

namespace App\Http\Controllers;

use App\Models\Rab;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RabsPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $projectId = $request->integer('project');

        $rabs = Rab::query()
            ->when($projectId > 0, fn ($query) => $query->where('project_id', $projectId))
            ->with('project:id,name')
            ->withCount('items')
            ->latest('id')
            ->get()
            ->map(fn (Rab $rab): array => [
                'id' => $rab->id,
                'projectId' => $rab->project_id,
                'projectName' => $rab->project?->name ?? '-',
                'totalBudget' => (float) ($rab->total_budget ?? 0),
                'itemCount' => $rab->items_count,
                'createdAt' => optional($rab->created_at)->format('Y-m-d'),
                'updatedAt' => optional($rab->updated_at)->format('Y-m-d'),
            ])
            ->all();

        return Inertia::render('Rabs', [
            'rabs' => $rabs,
            'activeProjectId' => $projectId > 0 ? $projectId : null,
        ]);
    }
}
