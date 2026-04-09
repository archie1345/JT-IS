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
        $clientId = $request->integer('client');

        $projects = Project::query()
            ->when($clientId > 0, fn ($query) => $query->where('client_id', $clientId))
            ->with([
                'client:id,name',
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
                'client' => $project->client?->name ?? '-',
                'estPrice' => (float) ($project->contract_value ?? 0),
                'deadline' => optional($project->end_date)->format('Y-m-d'),
                'paymentStatus' => $project->latestInvoice?->status ?? 'pending',
                'projectStatus' => $project->status,
            ]);

        return Inertia::render('Projects', [
            'projects' => $projects,
            'activeClientId' => $clientId > 0 ? $clientId : null,
        ]);
    }
}
