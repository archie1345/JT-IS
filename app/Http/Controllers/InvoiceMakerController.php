<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceMakerController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('InvoiceMaker', [
            'invoiceOptions' => Invoice::query()
                ->with(['project:id,client_id,name,contract_number,location', 'project.client:id,name,contact'])
                ->latest('id')
                ->limit(100)
                ->get()
                ->map(fn (Invoice $invoice): array => [
                    'value' => $invoice->id,
                    'label' => $invoice->invoice_number ?: 'Invoice #'.$invoice->id,
                    'hint' => $invoice->project?->name,
                    'invoice' => [
                        'id' => $invoice->id,
                        'project_id' => $invoice->project_id,
                        'project_name' => $invoice->project?->name,
                        'project_location' => $invoice->project?->location,
                        'contract_number' => $invoice->project?->contract_number,
                        'client_name' => $invoice->project?->client?->name,
                        'client_contact' => $invoice->project?->client?->contact,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->amount !== null ? (float) $invoice->amount : 0,
                        'tax_amount' => $invoice->tax_amount !== null ? (float) $invoice->tax_amount : 0,
                        'invoice_date' => optional($invoice->invoice_date)->format('Y-m-d'),
                        'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                        'status' => $invoice->status,
                        'description' => $invoice->description,
                    ],
                ])
                ->values()
                ->all(),
            'projectOptions' => Project::query()
                ->with('client:id,name,contact')
                ->orderBy('name')
                ->get(['id', 'client_id', 'name', 'contract_number', 'location'])
                ->map(fn (Project $project): array => [
                    'value' => $project->id,
                    'label' => $project->name ?? 'Project #'.$project->id,
                    'hint' => $project->client?->name,
                    'project' => [
                        'id' => $project->id,
                        'name' => $project->name,
                        'location' => $project->location,
                        'contract_number' => $project->contract_number,
                        'client_name' => $project->client?->name,
                        'client_contact' => $project->client?->contact,
                    ],
                ])
                ->values()
                ->all(),
        ]);
    }
}
