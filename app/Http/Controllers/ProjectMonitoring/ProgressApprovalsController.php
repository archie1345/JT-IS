<?php

namespace App\Http\Controllers\ProjectMonitoring;

class ProgressApprovalsController extends TableCrudController
{
    protected string $table = 'progress_approvals';

    protected function storeRules(): array
    {
        return [
            'progress_report_id' => ['required', 'integer', 'exists:progress_reports,id'],
            'approved_by_client' => ['nullable', 'boolean'],
            'approved_by_internal' => ['nullable', 'boolean'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'progress_report_id' => ['sometimes', 'required', 'integer', 'exists:progress_reports,id'],
            'approved_by_client' => ['sometimes', 'nullable', 'boolean'],
            'approved_by_internal' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    protected function usesTimestamps(): bool
    {
        return false;
    }
}
