<?php

namespace App\Http\Controllers\ProjectMonitoring;

class ProjectCostsController extends TableCrudController
{
    protected string $table = 'project_costs';

    protected function storeRules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'category' => ['nullable', 'string', 'max:100'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'date' => ['nullable', 'date'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'date' => ['sometimes', 'nullable', 'date'],
        ];
    }

    protected function usesTimestamps(): bool
    {
        return false;
    }
}
