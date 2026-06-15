<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectsPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $projects = Project::query()
            ->with([
                'latestInvoice' => fn ($query) => $query->select(
                    'invoices.id',
                    'invoices.project_id',
                    'invoices.status'
                ),
            ])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'projectName' => $project->name,
                'estPrice' => (float) ($project->contract_value ?? 0),
                'deadline' => optional($project->end_date)->format('Y-m-d'),
                'paymentStatus' => $project->latestInvoice?->status ?? 'pending',
                'projectStatus' => $project->status,
                'projectHealthStatus' => $project->projectHealthStatus(),
            ]);

        return Inertia::render('projects/Index', [
            'projects' => $projects,
        ]);
    }
}
