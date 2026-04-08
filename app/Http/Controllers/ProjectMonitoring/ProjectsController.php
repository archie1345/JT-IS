<?php

namespace App\Http\Controllers\ProjectMonitoring;

use Illuminate\Validation\Rule;

class ProjectsController extends TableCrudController
{
    protected string $table = 'projects';

    protected function storeRules(): array
    {
        return [
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'name' => ['nullable', 'string', 'max:200'],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'contract_value' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'location' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['planning', 'ongoing', 'completed'])],
        ];
    }

    protected function updateRules(int $id): array
    {
        return [
            'client_id' => ['sometimes', 'nullable', 'integer', 'exists:clients,id'],
            'name' => ['sometimes', 'nullable', 'string', 'max:200'],
            'contract_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contract_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'location' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'nullable', Rule::in(['planning', 'ongoing', 'completed'])],
        ];
    }
}
