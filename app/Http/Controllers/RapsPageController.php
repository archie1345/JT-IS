<?php

namespace App\Http\Controllers;

use App\Models\Rap;
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
        ]);
    }
}
