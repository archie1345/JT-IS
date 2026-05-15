<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class ProfitLossController extends Controller
{
    public function __invoke(): Response
    {
        $projects = Project::query()
            ->with('client:id,name')
            ->withSum('invoices as invoice_total', 'amount')
            ->withSum('projectCosts as cost_total', 'amount')
            ->orderBy('projects.name')
            ->get(['id', 'client_id', 'name', 'contract_value', 'status'])
            ->map(function (Project $project): array {
                $contractValue = (float) ($project->contract_value ?? 0);
                $invoiceTotal = (float) ($project->invoice_total ?? 0);
                $costTotal = (float) ($project->cost_total ?? 0);
                $recognizedRevenue = $invoiceTotal > 0 ? $invoiceTotal : $contractValue;
                $grossProfit = $recognizedRevenue - $costTotal;

                return [
                    'id' => $project->id,
                    'project_name' => $project->name,
                    'client_name' => $project->client?->name,
                    'status' => $project->status,
                    'contract_value' => $contractValue,
                    'invoice_total' => $invoiceTotal,
                    'cost_total' => $costTotal,
                    'gross_profit' => $grossProfit,
                    'margin_percent' => $recognizedRevenue > 0
                        ? round(($grossProfit / $recognizedRevenue) * 100, 1)
                        : 0.0,
                ];
            })
            ->values()
            ->all();

        $totals = [
            'contracts' => array_sum(array_column($projects, 'contract_value')),
            'invoiced' => array_sum(array_column($projects, 'invoice_total')),
            'costs' => array_sum(array_column($projects, 'cost_total')),
            'profit' => array_sum(array_column($projects, 'gross_profit')),
        ];

        return Inertia::render('ProfitLoss', [
            'records' => $projects,
            'totals' => $totals,
        ]);
    }
}
