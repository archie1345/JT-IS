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
                'message' => 'Budget Critical: realized cost is above RAP.',
            ];
        } elseif ($rapTotal > 0 && $realizedCost >= ($rapTotal * 0.9)) {
            $warnings[] = [
                'type' => 'budget',
                'level' => 'warning',
                'message' => 'Budget Warning: realized cost has reached at least 90% of RAP.',
            ];
        }

        if ($project->hasOverdueInvoice()) {
            $warnings[] = [
                'type' => 'payment',
                'level' => 'warning',
                'message' => 'Payment Warning: project has overdue invoice.',
            ];
        }

        $approvedProgress = $project->latestApprovedProgressPercent() ?? 0;
        if ($project->end_date && Carbon::parse($project->end_date)->isPast() && $approvedProgress < 100) {
            $warnings[] = [
                'type' => 'progress',
                'level' => 'critical',
                'message' => 'Progress Critical: project end date has passed without 100% approved progress.',
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
