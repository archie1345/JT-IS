<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Carbon;

class ProjectStatusService
{

    public function warnings(Project $project): array
    {
        $warnings = [];
        $rapTotal = $project->rapTotal();
        $realizedCost = $project->realizedCostTotal();

        if ($rapTotal > 0 && $realizedCost > $rapTotal) {
            $warnings[] = [
                'type' => 'budget',
                'level' => 'critical',
                'message' => 'Budget kritis: realisasi biaya sudah melebihi RAP.',
            ];
        } elseif ($rapTotal > 0 && $realizedCost >= ($rapTotal * 0.9)) {
            $warnings[] = [
                'type' => 'budget',
                'level' => 'warning',
                'message' => 'Peringatan budget: realisasi biaya sudah mencapai minimal 90% dari RAP.',
            ];
        }

        if ($project->hasOverdueInvoice()) {
            $warnings[] = [
                'type' => 'payment',
                'level' => 'warning',
                'message' => 'Peringatan pembayaran: proyek memiliki invoice terlambat.',
            ];
        }

        $approvedProgress = $project->latestApprovedProgressPercent() ?? 0;
        if ($project->end_date && Carbon::parse($project->end_date)->isPast() && $approvedProgress < 100) {
            $warnings[] = [
                'type' => 'progress',
                'level' => 'critical',
                'message' => 'Progress kritis: tanggal selesai proyek sudah lewat tanpa progress 100% disetujui.',
            ];
        }

        return $warnings;
    }

    public function status(Project $project): string
    {
        $warnings = $this->warnings($project);

        if (collect($warnings)->contains(fn (array $warning): bool => $warning['level'] === 'critical')) {
            return 'Critical';
        }

        if ($warnings !== []) {
            return 'Warning';
        }

        return 'On Track';
    }
}
