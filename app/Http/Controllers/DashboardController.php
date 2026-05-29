<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\ProgressReport;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('dashboard/Index', [
            'dashboardData' => [
                'projectStatus' => $this->projectStatus(),
                'invoiceStatus' => $this->invoiceStatus(),
                'costCategory' => $this->costCategory(),
                'monthlyInvoices' => $this->monthlyInvoices(),
                'monthlyCosts' => $this->monthlyCosts(),
                'progressTrend' => $this->progressTrend(),
                'totals' => $this->totals(),
                'mvpSummary' => $this->mvpSummary(),
                'problemProjects' => $this->problemProjects(),
                'recentProjects' => $this->recentProjects(),
                'recentProgress' => $this->recentProgress(),
            ],
        ]);
    }

    private function projectStatus(): array
    {
        return Project::query()
            ->select('status as label', DB::raw('COUNT(*) as value'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row): array => [
                'label' => ucfirst((string) $row->label),
                'value' => (float) $row->value,
            ])
            ->values()
            ->all();
    }

    private function invoiceStatus(): array
    {
        return Invoice::query()
            ->select('status as label', DB::raw('SUM(amount) as value'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row): array => [
                'label' => ucfirst((string) $row->label),
                'value' => (float) $row->value,
            ])
            ->values()
            ->all();
    }

    private function costCategory(): array
    {
        return ProjectCost::query()
            ->select(DB::raw("COALESCE(category, 'Uncategorized') as label"), DB::raw('SUM(amount) as value'))
            ->groupBy('category')
            ->orderByDesc('value')
            ->limit(8)
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->label,
                'value' => (float) $row->value,
            ])
            ->values()
            ->all();
    }

    private function monthlyInvoices(): array
    {
        return Invoice::query()
            ->select(DB::raw("DATE_FORMAT(invoice_date, '%Y-%m') as label"), DB::raw('SUM(amount) as value'))
            ->whereNotNull('invoice_date')
            ->groupBy('label')
            ->orderBy('label')
            ->limit(12)
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->label,
                'value' => (float) $row->value,
            ])
            ->values()
            ->all();
    }

    private function monthlyCosts(): array
    {
        return ProjectCost::query()
            ->select(DB::raw("DATE_FORMAT(date, '%Y-%m') as label"), DB::raw('SUM(amount) as value'))
            ->whereNotNull('date')
            ->groupBy('label')
            ->orderBy('label')
            ->limit(12)
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->label,
                'value' => (float) $row->value,
            ])
            ->values()
            ->all();
    }

    private function progressTrend(): array
    {
        return ProgressReport::query()
            ->select(DB::raw("DATE_FORMAT(report_date, '%Y-%m-%d') as label"), DB::raw('AVG(progress_percent) as value'))
            ->whereNotNull('report_date')
            ->groupBy('label')
            ->orderBy('label')
            ->limit(12)
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->label,
                'value' => round((float) $row->value, 2),
            ])
            ->values()
            ->all();
    }

    private function totals(): array
    {
        return [
            ['label' => 'Projects', 'value' => (float) Project::query()->count(), 'format' => 'number'],
            ['label' => 'Contract Value', 'value' => (float) Project::query()->sum('contract_value'), 'format' => 'currency'],
            ['label' => 'Invoices', 'value' => (float) Invoice::query()->sum('amount'), 'format' => 'currency'],
            ['label' => 'Payments', 'value' => (float) Payment::query()->sum('amount'), 'format' => 'currency'],
            ['label' => 'Costs', 'value' => (float) ProjectCost::query()->sum('amount'), 'format' => 'currency'],
        ];
    }

    private function mvpSummary(): array
    {
        $projects = Project::query()->get();
        $statuses = $projects->map(fn (Project $project): string => $project->mvpStatus());

        return [
            ['label' => 'Total Nilai Kontrak', 'value' => (float) $projects->sum('contract_value'), 'format' => 'currency'],
            ['label' => 'Proyek Aktif', 'value' => (float) $projects->whereIn('status', ['planning', 'ongoing'])->count(), 'format' => 'number'],
            ['label' => 'Total Realisasi Biaya', 'value' => (float) ProjectCost::query()->sum('amount'), 'format' => 'currency'],
            ['label' => 'Total Tagihan', 'value' => (float) Invoice::query()->sum('amount'), 'format' => 'currency'],
            ['label' => 'Total Pembayaran', 'value' => (float) Payment::query()->sum('amount'), 'format' => 'currency'],
            ['label' => 'Tagihan Jatuh Tempo', 'value' => (float) Invoice::query()->where('status', 'overdue')->count(), 'format' => 'number'],
            ['label' => 'Proyek Warning', 'value' => (float) $statuses->filter(fn (string $status): bool => $status === 'Warning')->count(), 'format' => 'number'],
            ['label' => 'Proyek Critical', 'value' => (float) $statuses->filter(fn (string $status): bool => $status === 'Critical')->count(), 'format' => 'number'],
        ];
    }

    private function problemProjects(): array
    {
        return Project::query()
            ->with('client:id,name')
            ->latest('id')
            ->get()
            ->map(function (Project $project): array {
                return [
                    'id' => $project->id,
                    'name' => $project->name ?? 'Untitled project',
                    'client' => $project->client?->name ?? '-',
                    'status' => $project->mvpStatus(),
                    'warnings' => $project->mvpWarnings(),
                    'contractValue' => (float) ($project->contract_value ?? 0),
                    'realizedCost' => $project->realizedCostTotal(),
                    'rapTotal' => $project->rapTotal(),
                    'approvedProgress' => $project->latestApprovedProgressPercent() ?? 0,
                ];
            })
            ->filter(fn (array $project): bool => in_array($project['status'], ['Warning', 'Critical'], true))
            ->values()
            ->all();
    }

    private function recentProjects(): array
    {
        return Project::query()
            ->with('client:id,name')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name ?? 'Untitled project',
                'client' => $project->client?->name ?? '-',
                'status' => $project->mvpStatus(),
                'dbStatus' => $project->status,
                'approvedProgress' => $project->latestApprovedProgressPercent() ?? 0,
                'endDate' => optional($project->end_date)->format('Y-m-d'),
            ])
            ->all();
    }

    private function recentProgress(): array
    {
        return ProgressReport::query()
            ->with('project:id,name,client_id', 'project.client:id,name')
            ->latest('report_date')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (ProgressReport $report): array => [
                'id' => $report->id,
                'projectId' => $report->project_id,
                'projectName' => $report->project?->name ?? 'Untitled project',
                'client' => $report->project?->client?->name ?? '-',
                'percent' => (float) ($report->progress_percent ?? 0),
                'date' => optional($report->report_date)->format('Y-m-d'),
                'approved' => (bool) ($report->approved_by_client && $report->approved_by_internal),
                'documentNumber' => $report->document_number,
            ])
            ->all();
    }
}
