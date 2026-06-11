<?php

namespace App\Support;

use App\Models\FundRequest;
use App\Models\Invoice;
use App\Models\ProgressReport;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Models\Rab;
use App\Models\Rap;
use App\Models\Tender;

class ProjectDocumentData
{
    public function groups(Project $project): array
    {
        if (! $project->exists) {
            return [];
        }

        $rabsCount = $project->rabs()->count();
        $rapsCount = $project->raps()->count();
        $progressReportsCount = $project->progressReports()->count();
        $invoicesCount = $project->invoices()->count();
        $projectCostsCount = $project->projectCosts()->count();
        $fundRequestsCount = $project->fundRequests()->count();
        $pipelineCount = Tender::query()
            ->where('project_id', $project->id)
            ->count();

        $rabs = $project->rabs()
            ->withCount('items')
            ->withSum('items as computed_total_budget', 'total_price')
            ->latest('id')
            ->get()
            ->map(fn (Rab $rab): array => [
                'id' => $rab->id,
                'documentNumber' => $rab->document_number,
                'documentDate' => optional($rab->document_date)->format('Y-m-d'),
                'totalBudget' => (float) ($rab->computed_total_budget ?? 0),
                'itemCount' => $rab->items_count,
                'notes' => $rab->notes,
                'url' => route('rabs.show', $rab),
                'createdAt' => optional($rab->created_at)->format('Y-m-d'),
            ])
            ->all();

        $raps = $project->raps()
            ->withCount('items')
            ->withSum('items as computed_total_budget', 'total_price')
            ->latest('id')
            ->get()
            ->map(fn (Rap $rap): array => [
                'id' => $rap->id,
                'documentNumber' => $rap->document_number,
                'documentDate' => optional($rap->document_date)->format('Y-m-d'),
                'totalBudget' => (float) ($rap->computed_total_budget ?? 0),
                'itemCount' => $rap->items_count,
                'notes' => $rap->notes,
                'url' => route('raps.show', $rap),
                'createdAt' => optional($rap->created_at)->format('Y-m-d'),
            ])
            ->all();

        return [
            [
                'key' => 'rabs',
                'label' => 'RAB',
                'description' => 'Dokumen anggaran pendapatan proyek.',
                'count' => $rabsCount,
                'listUrl' => route('rabs', ['project' => $project->id]),
                'createKind' => 'rab',
                'records' => array_map(fn (array $rab): array => [
                    'id' => $rab['id'],
                    'title' => $rab['documentNumber'] ?: 'RAB #'.$rab['id'],
                    'detail' => trim(($rab['documentDate'] ?? 'Tanggal belum diisi').' - '.$rab['itemCount'].' item'),
                    'value' => $this->formatMoney((float) $rab['totalBudget']),
                    'url' => $rab['url'],
                ], array_slice($rabs, 0, 3)),
            ],
            [
                'key' => 'raps',
                'label' => 'RAP',
                'description' => 'Dokumen anggaran pelaksanaan proyek.',
                'count' => $rapsCount,
                'listUrl' => route('raps', ['project' => $project->id]),
                'createKind' => 'rap',
                'records' => array_map(fn (array $rap): array => [
                    'id' => $rap['id'],
                    'title' => $rap['documentNumber'] ?: 'RAP #'.$rap['id'],
                    'detail' => trim(($rap['documentDate'] ?? 'Tanggal belum diisi').' - '.$rap['itemCount'].' item'),
                    'value' => $this->formatMoney((float) $rap['totalBudget']),
                    'url' => $rap['url'],
                ], array_slice($raps, 0, 3)),
            ],
            [
                'key' => 'progress_reports',
                'label' => 'Progress / BAMC',
                'description' => 'Laporan progress dan approval pekerjaan.',
                'count' => $progressReportsCount,
                'listUrl' => route('progress-updates.index', ['project' => $project->id]),
                'createKind' => 'progress_report',
                'records' => $project->progressReports()
                    ->latest('report_date')
                    ->latest('id')
                    ->limit(3)
                    ->get()
                    ->map(fn (ProgressReport $report): array => [
                        'id' => $report->id,
                        'title' => $report->document_number ?: 'Progress #'.$report->id,
                        'detail' => trim(($report->document_type ?? 'Progress').' - '.optional($report->report_date)->format('Y-m-d')),
                        'value' => number_format((float) $report->progress_percent, 2, ',', '.').'%',
                        'url' => route('progress-updates.show', $report->id),
                    ])
                    ->all(),
            ],
            [
                'key' => 'invoices',
                'label' => 'Invoice',
                'description' => 'Tagihan dan status pembayaran proyek.',
                'count' => $invoicesCount,
                'listUrl' => route('invoices.index', ['project' => $project->id]),
                'createKind' => 'invoice',
                'records' => $project->invoices()
                    ->latest('invoice_date')
                    ->latest('id')
                    ->limit(3)
                    ->get()
                    ->map(fn (Invoice $invoice): array => [
                        'id' => $invoice->id,
                        'title' => $invoice->invoice_number ?: 'Invoice #'.$invoice->id,
                        'detail' => trim(($this->statusLabel($invoice->status ?? 'pending')).' - '.optional($invoice->invoice_date)->format('Y-m-d')),
                        'value' => $this->formatMoney((float) $invoice->amount),
                        'url' => route('invoices.show', $invoice->id),
                    ])
                    ->all(),
            ],
            [
                'key' => 'project_costs',
                'label' => 'Realisasi Biaya',
                'description' => 'Catatan biaya aktual dan bukti pendukung.',
                'count' => $projectCostsCount,
                'listUrl' => route('project-costs.index', ['project' => $project->id]),
                'createKind' => 'project_cost',
                'records' => $project->projectCosts()
                    ->latest('date')
                    ->latest('id')
                    ->limit(3)
                    ->get()
                    ->map(fn (ProjectCost $cost): array => [
                        'id' => $cost->id,
                        'title' => $cost->reference_number ?: 'Biaya #'.$cost->id,
                        'detail' => trim(($cost->category ?? 'Biaya').' - '.optional($cost->date)->format('Y-m-d')),
                        'value' => $this->formatMoney((float) $cost->amount),
                        'url' => route('project-costs.show', $cost->id),
                    ])
                    ->all(),
            ],
            [
                'key' => 'pipeline',
                'label' => 'Pipeline / Laporan',
                'description' => 'Dokumen tender, laporan, atau pipeline proyek.',
                'count' => $pipelineCount,
                'listUrl' => route('pipeline', ['project' => $project->id]),
                'createKind' => 'pipeline',
                'records' => Tender::query()
                    ->where('project_id', $project->id)
                    ->latest('document_date')
                    ->latest('id')
                    ->limit(3)
                    ->get()
                    ->map(fn (Tender $tender): array => [
                        'id' => $tender->id,
                        'title' => $tender->document_number ?: ($tender->title ?: 'Pipeline #'.$tender->id),
                        'detail' => trim(($this->statusLabel($tender->status ?? 'open')).' - '.optional($tender->document_date)->format('Y-m-d')),
                        'value' => $tender->value ? $this->formatMoney((float) $tender->value) : null,
                        'url' => route('pipeline.show', $tender->id),
                    ])
                    ->all(),
            ],
            [
                'key' => 'fund_requests',
                'label' => 'Fund Request',
                'description' => 'Pengajuan dana terkait proyek.',
                'count' => $fundRequestsCount,
                'listUrl' => route('fund-requests.index', ['project' => $project->id]),
                'createKind' => 'fund_request',
                'records' => $project->fundRequests()
                    ->latest('id')
                    ->limit(3)
                    ->get()
                    ->map(fn (FundRequest $request): array => [
                        'id' => $request->id,
                        'title' => 'Fund Request #'.$request->id,
                        'detail' => $this->statusLabel($request->status ?? 'pending'),
                        'value' => $this->formatMoney((float) $request->amount),
                        'url' => route('fund-requests.index', ['project' => $project->id]),
                    ])
                    ->all(),
            ],
        ];
    }

    public function connections(Project $project): array
    {
        if (! $project->exists) {
            return [];
        }

        $connections = [
            [
                'value' => 'project:general',
                'label' => 'Dokumen umum proyek',
                'hint' => $project->name,
                'componentType' => 'project',
                'componentId' => null,
                'projectId' => $project->id,
            ],
        ];

        foreach ($project->rabs()->latest('id')->get(['id', 'project_id', 'total_budget']) as $rab) {
            $connections[] = [
                'value' => 'rab:'.$rab->id,
                'label' => 'RAB #'.$rab->id,
                'hint' => 'Budget '.number_format((float) $rab->total_budget, 0, ',', '.'),
                'componentType' => 'rab',
                'componentId' => $rab->id,
                'projectId' => $project->id,
            ];
        }

        foreach ($project->raps()->latest('id')->get(['id', 'project_id', 'total_budget']) as $rap) {
            $connections[] = [
                'value' => 'rap:'.$rap->id,
                'label' => 'RAP #'.$rap->id,
                'hint' => 'Budget '.number_format((float) $rap->total_budget, 0, ',', '.'),
                'componentType' => 'rap',
                'componentId' => $rap->id,
                'projectId' => $project->id,
            ];
        }

        foreach ($project->invoices()->latest('id')->get(['id', 'project_id', 'invoice_date', 'status']) as $invoice) {
            $connections[] = [
                'value' => 'invoice:'.$invoice->id,
                'label' => 'Invoice #'.$invoice->id,
                'hint' => trim(($invoice->status ?? '').' '.optional($invoice->invoice_date)->format('Y-m-d')),
                'componentType' => 'invoice',
                'componentId' => $invoice->id,
                'projectId' => $project->id,
            ];
        }

        foreach ($project->projectCosts()->latest('date')->latest('id')->get(['id', 'project_id', 'category', 'date']) as $cost) {
            $connections[] = [
                'value' => 'project_cost:'.$cost->id,
                'label' => 'Biaya #'.$cost->id,
                'hint' => trim(($cost->category ?? 'Biaya').' '.optional($cost->date)->format('Y-m-d')),
                'componentType' => 'project_cost',
                'componentId' => $cost->id,
                'projectId' => $project->id,
            ];
        }

        foreach ($project->progressReports()->latest('report_date')->latest('id')->get(['id', 'project_id', 'progress_percent', 'report_date']) as $report) {
            $connections[] = [
                'value' => 'progress_report:'.$report->id,
                'label' => 'Progress #'.$report->id,
                'hint' => trim(($report->progress_percent ?? 0).'% '.optional($report->report_date)->format('Y-m-d')),
                'componentType' => 'progress_report',
                'componentId' => $report->id,
                'projectId' => $project->id,
            ];
        }

        foreach (Tender::query()->where('project_id', $project->id)->latest('id')->get(['id', 'project_id', 'document_number', 'title']) as $tender) {
            $connections[] = [
                'value' => 'pipeline:'.$tender->id,
                'label' => 'Pipeline #'.$tender->id,
                'hint' => $tender->document_number ?: $tender->title,
                'componentType' => 'pipeline',
                'componentId' => $tender->id,
                'projectId' => $project->id,
            ];
        }

        foreach ($project->fundRequests()->latest('id')->get(['id', 'project_id', 'amount', 'status']) as $request) {
            $connections[] = [
                'value' => 'fund_request:'.$request->id,
                'label' => 'Fund Request #'.$request->id,
                'hint' => trim($this->statusLabel($request->status ?? '').' '.number_format((float) $request->amount, 0, ',', '.')),
                'componentType' => 'fund_request',
                'componentId' => $request->id,
                'projectId' => $project->id,
            ];
        }

        return $connections;
    }

    private function formatMoney(float $value): string
    {
        return 'Rp '.number_format($value, 0, ',', '.');
    }

    private function statusLabel(?string $status): string
    {
        return [
            'pending' => 'Menunggu',
            'paid' => 'Lunas',
            'overdue' => 'Terlambat',
            'open' => 'Terbuka',
            'submitted' => 'Diajukan',
            'won' => 'Menang',
            'lost' => 'Kalah',
        ][$status ?? ''] ?? ($status ?? '');
    }
}
