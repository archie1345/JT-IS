<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Support\ProjectDocumentData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectDetailsController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('projects/Show', $this->pagePayload(
            project: null,
            mode: 'create',
            defaults: [
                'clientId' => $request->integer('client') ?: null,
            ],
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        $project = Project::create([
            'client_id' => $data['client_id'],
            'name' => $data['name'],
            'contract_number' => $data['contract_number'] ?? null,
            'contract_value' => $data['contract_value'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'location' => $data['location'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'status' => $data['status'],
        ]);

        $this->syncPaymentStatus($project, $data['payment_status'], (float) $data['contract_value']);
        return to_route('projects.show', $project)->with('success', 'Proyek berhasil dibuat.');
    }

    public function show(Project $project): Response
    {
        return Inertia::render('projects/Show', $this->pagePayload($project, 'edit'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validatePayload($request, $project->id);

        $project->update([
            'client_id' => $data['client_id'],
            'name' => $data['name'],
            'contract_number' => $data['contract_number'] ?? null,
            'contract_value' => $data['contract_value'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'location' => $data['location'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'status' => $data['status'],
        ]);

        $this->syncPaymentStatus($project, $data['payment_status'], (float) $data['contract_value']);
        return to_route('projects.show', $project)->with('success', 'Proyek berhasil diupdate.');
    }

    protected function validatePayload(Request $request, ?int $projectId = null): array
    {
        return $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:200'],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'contract_value' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'location' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'status' => ['required', Rule::in(['planning', 'ongoing', 'completed'])],
            'payment_status' => ['required', Rule::in(['pending', 'partial', 'paid', 'overdue'])],
        ]);
    }

    protected function syncPaymentStatus(Project $project, string $status, float $amount): void
    {
        $invoice = Invoice::query()
            ->where('project_id', $project->id)
            ->latest('id')
            ->first();

        $payload = [
            'status' => $status,
            'amount' => $amount,
            'invoice_date' => now()->toDateString(),
        ];

        if ($invoice) {
            $invoice->update($payload);
            return;
        }

        $project->invoices()->create($payload);
    }

    protected function pagePayload(?Project $project, string $mode, array $defaults = []): array
    {
        $project ??= new Project();

        if ($project->exists) {
            $project->load([
                'client:id,name,contact',
                'latestInvoice' => fn($query) => $query->select(
                    'invoices.id',
                    'invoices.project_id',
                    'invoices.status',
                    'invoices.amount',
                    'invoices.invoice_date',
                ),
            ]);
        }

        $latestProgressReport = $project->exists
            ? $project->latestProgressReport()
            : null;
        $latestApprovedProgressReport = $project->exists
            ? $project->latestApprovedProgressReport()
            : null;

        $uploadedDocuments = $project->exists
            ? $project->documents()
            ->with('project:id,name')
            ->latest()
            ->get()
            ->map(fn ($document): array => ProjectDocumentsController::serialize($document))
            ->all()
            : [];

        $projectStatus = $project->exists ? $project->status : 'planning';
        $paymentStatus = $project->exists
            ? ($project->latestInvoice?->status ?? 'pending')
            : 'pending';

        $reportScore = (float) ($latestProgressReport?->progress_percent ?? 0);

        $documentData = app(ProjectDocumentData::class);
        $documentGroups = $documentData->groups($project);
        $documentConnections = $documentData->connections($project);

        return [
            'mode' => $mode,
            'project' => [
                'id' => $project->exists ? $project->id : null,
                'clientId' => $project->exists ? $project->client_id : ($defaults['clientId'] ?? null),
                'name' => $project->exists ? $project->name : '',
                'clientName' => $project->exists ? $project->client?->name : null,
                'clientContact' => $project->exists ? $project->client?->contact : null,
                'contractNumber' => $project->exists ? $project->contract_number : '',
                'contractValue' => (float) ($project->exists ? $project->contract_value ?? 0 : 0),
                'location' => $project->exists ? $project->location : '',
                'latitude' => $project->exists ? (float) $project->latitude : null,
                'longitude' => $project->exists ? (float) $project->longitude : null,
                'startDate' => $project->exists ? optional($project->start_date)->format('Y-m-d') : null,
                'endDate' => $project->exists ? optional($project->end_date)->format('Y-m-d') : null,
                'status' => $projectStatus,
                'projectHealthStatus' => $project->exists
                    ? $project->projectHealthStatus()
                    : 'On Track',
                'warnings' => $project->exists ? $project->projectHealthWarnings() : [],
                'rabTotal' => $project->exists ? $project->rabTotal() : 0,
                'rapTotal' => $project->exists ? $project->rapTotal() : 0,
                'realizedCostTotal' => $project->exists ? $project->realizedCostTotal() : 0,
                'paymentStatus' => $paymentStatus,
                'latestProgressPercent' => $latestProgressReport?->progress_percent,
                'latestProgressNote' => $latestProgressReport?->description,
                'latestProgressApproved' => $latestProgressReport
                    ? (bool) ($latestProgressReport->approved_by_client && $latestProgressReport->approved_by_internal)
                    : false,
                'latestApprovedProgressPercent' => $latestApprovedProgressReport?->progress_percent,
            ],
            'clients' => Client::query()
                ->orderBy('name')
                ->get(['id', 'name', 'contact'])
                ->map(fn(Client $client): array => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'contact' => $client->contact,
                ])
                ->all(),
            'documentGroups' => $documentGroups,
            'uploadedDocuments' => $uploadedDocuments,
            'documentConnections' => $documentConnections,
            'progress' => [
                'reportScore' => $reportScore,
                'projectStatusScore' => 0,
                'paymentStatusScore' => 0,
                'overallProgress' => $reportScore,
            ],
            'recentReport' => $latestProgressReport ? [
                'date' => optional($latestProgressReport->report_date)->format('Y-m-d'),
                'description' => $latestProgressReport->description,
                'percent' => $latestProgressReport->progress_percent,
            ] : null,
        ];
    }
}
