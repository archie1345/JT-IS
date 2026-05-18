<?php

namespace App\Http\Controllers\ProjectMonitoring;

class RapsController extends TableCrudController
{
    protected string $table = 'raps';

    protected function storeRules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'document_number' => ['nullable', 'string', 'max:120'],
            'document_date' => ['nullable', 'date'],
            'total_budget' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'project_id' => ['sometimes', 'required', 'integer', 'exists:projects,id'],
            'document_number' => ['sometimes', 'nullable', 'string', 'max:120'],
            'document_date' => ['sometimes', 'nullable', 'date'],
            'total_budget' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
