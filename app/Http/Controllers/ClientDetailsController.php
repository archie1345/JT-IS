<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientDetailsController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('ClientsDetails', [
            'mode' => 'create',
            'client' => [
                'id' => null,
                'name' => '',
                'contact' => null,
                'projectCount' => 0,
                'totalProjectValue' => 0,
                'activeProjects' => 0,
                'completedProjects' => 0,
                'firstProjectDate' => null,
                'lastProjectDate' => null,
                'lastUpdated' => null,
            ],
            'projects' => [],
            'quickLinks' => [],
        ]);
    }

    public function show(Client $client): Response
    {
        $client->loadCount('projects');

        $projects = $client->projects()
            ->latest('id')
            ->get(['id', 'client_id', 'name', 'contract_number', 'contract_value', 'status', 'start_date', 'end_date', 'updated_at'])
            ->map(fn ($project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'contractNumber' => $project->contract_number,
                'contractValue' => (float) $project->contract_value,
                'status' => $project->status,
                'startDate' => $project->start_date ? Carbon::parse($project->start_date)->format('Y-m-d') : null,
                'endDate' => $project->end_date ? Carbon::parse($project->end_date)->format('Y-m-d') : null,
            ])
            ->all();

        $activeProjects = $client->projects()->where('status', 'ongoing')->count();
        $completedProjects = $client->projects()->where('status', 'completed')->count();
        $firstProjectDate = $client->projects()->orderBy('start_date')->value('start_date');
        $lastProjectDate = $client->projects()->orderByDesc('end_date')->value('end_date');
        $lastUpdated = $client->projects()->orderByDesc('updated_at')->value('updated_at') ?? $client->updated_at;

        return Inertia::render('ClientsDetails', [
            'mode' => 'edit',
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'contact' => $client->contact,
                'projectCount' => $client->projects_count,
                'totalProjectValue' => (float) $client->projects()->sum('contract_value'),
                'activeProjects' => $activeProjects,
                'completedProjects' => $completedProjects,
                'firstProjectDate' => $firstProjectDate ? Carbon::parse($firstProjectDate)->format('Y-m-d') : null,
                'lastProjectDate' => $lastProjectDate ? Carbon::parse($lastProjectDate)->format('Y-m-d') : null,
                'lastUpdated' => $lastUpdated ? Carbon::parse($lastUpdated)->format('Y-m-d') : null,
            ],
            'projects' => $projects,
            'quickLinks' => [
                [
                    'label' => 'Open Projects',
                    'detail' => 'View all projects for this client',
                    'url' => route('projects', ['client' => $client->id]),
                ],
                [
                    'label' => 'Open Invoices',
                    'detail' => 'Go to invoice management',
                    'url' => route('invoices.index'),
                ],
                [
                    'label' => 'Open Fund Requests',
                    'detail' => 'Review funding requests',
                    'url' => route('fund-requests.index'),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:150'],
        ]);

        $client = Client::create($data);

        return to_route('client.show', $client)->with('success', 'Client created successfully.');
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:150'],
        ]);

        $client->update($data);

        return to_route('client.show', $client)->with('success', 'Client updated successfully.');
    }
}
