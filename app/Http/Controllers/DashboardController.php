<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
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
        return Inertia::render('Dashboard', [
            'dashboardData' => [
                'projectStatus' => $this->projectStatus(),
                'invoiceStatus' => $this->invoiceStatus(),
                'costCategory' => $this->costCategory(),
                'monthlyInvoices' => $this->monthlyInvoices(),
                'monthlyCosts' => $this->monthlyCosts(),
                'progressTrend' => $this->progressTrend(),
                'totals' => $this->totals(),
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
            ['label' => 'Projects', 'value' => (float) Project::query()->count()],
            ['label' => 'Contract Value', 'value' => (float) Project::query()->sum('contract_value')],
            ['label' => 'Invoices', 'value' => (float) Invoice::query()->sum('amount')],
            ['label' => 'Costs', 'value' => (float) ProjectCost::query()->sum('amount')],
        ];
    }
}
