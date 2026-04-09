<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Inertia\Inertia;
use Inertia\Response;

class ClientsPageController extends Controller
{
    public function __invoke(): Response
    {
        $clients = Client::query()
            ->withCount('projects')
            ->withSum('projects', 'contract_value')
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client): array => [
                'id' => $client->id,
                'name' => $client->name,
                'contact' => $client->contact,
                'projectCount' => $client->projects_count,
                'totalProjectValue' => (float) ($client->projects_sum_contract_value ?? 0),
            ]);

        return Inertia::render('Clients', [
            'clients' => $clients,
        ]);
    }
}
