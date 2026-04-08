<?php

namespace App\Http\Controllers\ProjectMonitoring;

class RapsController extends TableCrudController
{
    protected string $table = 'raps';

    protected function storeRules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'total_budget' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'total_budget' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
