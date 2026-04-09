<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\ProjectDocument;
use App\Models\Project;
use App\Models\ProgressReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectDetailsController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('ProjectDetails', $this->pagePayload(
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
            'status' => $data['status'],
        ]);

        $this->syncPaymentStatus($project, $data['payment_status'], (float) $data['contract_value']);
        $this->syncProgressReport(
            $project,
            $data['progress_percent'] ?? null,
            $data['progress_note'] ?? null,
        );

        return to_route('projects.show', $project)->with('success', 'Project created successfully.');
    }

    public function show(Project $project): Response
    {
        return Inertia::render('ProjectDetails', $this->pagePayload($project, 'edit'));
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
            'status' => $data['status'],
        ]);

        $this->syncPaymentStatus($project, $data['payment_status'], (float) $data['contract_value']);
        $this->syncProgressReport(
            $project,
            $data['progress_percent'] ?? null,
            $data['progress_note'] ?? null,
        );

        return to_route('projects.show', $project)->with('success', 'Project updated successfully.');
    }

    public function storeDocument(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['file', 'max:10240'],
        ]);

        foreach ($data['documents'] as $file) {
            $directory = "projects/{$project->id}";
            $storedPath = $file->store($directory, 'public');

            ProjectDocument::create([
                'project_id' => $project->id,
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'original_name' => $file->getClientOriginalName(),
                'path' => $storedPath,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return to_route('projects.show', $project)->with('success', 'Documents uploaded successfully.');
    }

    public function showDocument(ProjectDocument $projectDocument)
    {
        abort_unless(
            $projectDocument->project()->exists(),
            404,
        );

        return Storage::disk('public')->response(
            $projectDocument->path,
            $projectDocument->original_name,
        );
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
            'status' => ['required', Rule::in(['planning', 'ongoing', 'completed'])],
            'payment_status' => ['required', Rule::in(['pending', 'partial', 'paid', 'overdue'])],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'progress_note' => ['nullable', 'string'],
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

    protected function syncProgressReport(Project $project, ?int $progressPercent, ?string $progressNote): void
    {
        if ($progressPercent === null && blank($progressNote)) {
            return;
        }

        $report = $project->progressReports()->latest('report_date')->first();
        $payload = [
            'progress_percent' => $progressPercent ?? $report?->progress_percent ?? 0,
            'report_date' => now()->toDateString(),
            'description' => $progressNote ?? $report?->description,
        ];

        if ($report) {
            $report->update($payload);
            return;
        }

        $project->progressReports()->create($payload);
    }

    protected function pagePayload(?Project $project, string $mode, array $defaults = []): array
    {
        $project ??= new Project();

        if ($project->exists) {
            $project->load([
                'client:id,name,contact',
                'latestInvoice' => fn ($query) => $query->select(
                    'invoices.id',
                    'invoices.project_id',
                    'invoices.status',
                    'invoices.amount',
                    'invoices.invoice_date',
                ),
            ]);
        }

        $latestProgressReport = $project->exists
            ? $project->progressReports()->latest('report_date')->first(['id', 'project_id', 'progress_percent', 'report_date', 'description'])
            : null;

        $rabsCount = $project->exists ? $project->rabs()->count() : 0;
        $rapsCount = $project->exists ? $project->raps()->count() : 0;
        $progressReportsCount = $project->exists ? $project->progressReports()->count() : 0;
        $invoicesCount = $project->exists ? $project->invoices()->count() : 0;
        $fundRequestsCount = $project->exists ? $project->fundRequests()->count() : 0;
        $uploadedDocuments = $project->exists
            ? $project->documents()
                ->latest()
                ->get(['id', 'name', 'original_name', 'path', 'mime_type', 'size', 'created_at'])
                ->map(fn (ProjectDocument $document): array => [
                    'id' => $document->id,
                    'name' => $document->name,
                    'originalName' => $document->original_name,
                    'url' => route('projects.documents.show', $document),
                    'mimeType' => $document->mime_type,
                    'size' => $document->size,
                    'createdAt' => optional($document->created_at)->format('Y-m-d H:i'),
                ])
                ->all()
            : [];

        $projectStatus = $project->exists ? $project->status : 'planning';
        $paymentStatus = $project->exists
            ? ($project->latestInvoice?->status ?? 'pending')
            : 'pending';

        $reportScore = (int) ($latestProgressReport?->progress_percent ?? 0);
        $projectStatusScore = match ($projectStatus) {
            'planning' => 25,
            'ongoing' => 65,
            'completed' => 100,
            default => 0,
        };
        $paymentStatusScore = match ($paymentStatus) {
            'pending' => 20,
            'partial' => 55,
            'paid' => 100,
            'overdue' => 35,
            default => 0,
        };

        $overallProgress = (int) round(
            ($reportScore * 0.55) +
            ($projectStatusScore * 0.25) +
            ($paymentStatusScore * 0.20)
        );

        $documents = [
            [
                'label' => 'Contract / RAB',
                'detail' => $rabsCount > 0 ? $rabsCount.' linked record(s)' : 'No RAB linked yet',
                'status' => $rabsCount > 0 ? 'available' : 'missing',
                'url' => $project->exists ? route('rabs', ['project' => $project->id]) : null,
            ],
            [
                'label' => 'RAP',
                'detail' => $rapsCount > 0 ? $rapsCount.' linked record(s)' : 'No RAP linked yet',
                'status' => $rapsCount > 0 ? 'available' : 'missing',
                'url' => $project->exists ? route('raps', ['project' => $project->id]) : null,
            ],
            [
                'label' => 'Progress Reports',
                'detail' => $progressReportsCount > 0 ? $progressReportsCount.' report(s)' : 'No report submitted yet',
                'status' => $progressReportsCount > 0 ? 'available' : 'missing',
                'url' => $project->exists ? route('projects.show', $project) : null,
            ],
            [
                'label' => 'Invoices',
                'detail' => $invoicesCount > 0 ? $invoicesCount.' invoice(s)' : 'No invoice created yet',
                'status' => $invoicesCount > 0 ? 'available' : 'missing',
                'url' => $project->exists ? route('invoices.index') : null,
            ],
            [
                'label' => 'Fund Requests',
                'detail' => $fundRequestsCount > 0 ? $fundRequestsCount.' request(s)' : 'No fund request yet',
                'status' => $fundRequestsCount > 0 ? 'available' : 'missing',
                'url' => $project->exists ? route('fund-requests.index') : null,
            ],
        ];

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
                'startDate' => $project->exists ? optional($project->start_date)->format('Y-m-d') : null,
                'endDate' => $project->exists ? optional($project->end_date)->format('Y-m-d') : null,
                'status' => $projectStatus,
                'paymentStatus' => $paymentStatus,
                'latestProgressPercent' => $latestProgressReport?->progress_percent,
                'latestProgressNote' => $latestProgressReport?->description,
            ],
            'clients' => Client::query()
                ->orderBy('name')
                ->get(['id', 'name', 'contact'])
                ->map(fn (Client $client): array => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'contact' => $client->contact,
                ])
                ->all(),
            'documents' => $documents,
            'uploadedDocuments' => $uploadedDocuments,
            'progress' => [
                'reportScore' => $reportScore,
                'projectStatusScore' => $projectStatusScore,
                'paymentStatusScore' => $paymentStatusScore,
                'overallProgress' => $overallProgress,
            ],
            'recentReport' => $latestProgressReport ? [
                'date' => optional($latestProgressReport->report_date)->format('Y-m-d'),
                'description' => $latestProgressReport->description,
                'percent' => $latestProgressReport->progress_percent,
            ] : null,
        ];
    }
}
