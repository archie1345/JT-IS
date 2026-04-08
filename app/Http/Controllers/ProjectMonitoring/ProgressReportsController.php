<?php

namespace App\Http\Controllers\ProjectMonitoring;

class ProgressReportsController extends TableCrudController
{
    protected string $table = 'progress_reports';

    protected function storeRules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'report_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'progress_percent' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'report_date' => ['sometimes', 'nullable', 'date'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
